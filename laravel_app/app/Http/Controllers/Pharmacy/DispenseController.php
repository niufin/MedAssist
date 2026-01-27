<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\Consultation;
use App\Models\DispenseItem;
use App\Models\DispenseOrder;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use App\Models\AuditLog;
use App\Models\PharmacyInvoice;
use App\Models\PharmacyInvoiceItem;
use App\Models\PrescriptionFulfillment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class DispenseController extends Controller
{
    use ResolvesPharmacyStore;

    protected function normalizeSearchToken(?string $s): string
    {
        $s = (string) $s;
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/i', '', $s) ?? '';
        return $s;
    }

    protected function medicineMatchScore(string $query, ?Medicine $med): int
    {
        if (!$med) {
            return 0;
        }

        $q = $this->normalizeSearchToken($query);
        if ($q === '') {
            return 0;
        }

        $pi = $this->normalizeSearchToken($med->primary_ingredient);
        $name = $this->normalizeSearchToken($med->name);
        $brand = $this->normalizeSearchToken($med->brand_name);

        $gd = $med->generic_display;
        $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
        $comp = $this->normalizeSearchToken(is_string($compText) ? $compText : '');

        $score = 0;
        if ($pi !== '' && str_contains($pi, $q)) {
            $score = 4;
        } elseif ($comp !== '' && str_contains($comp, $q)) {
            $score = 3;
        } elseif ($name !== '' && str_contains($name, $q)) {
            $score = 2;
        } elseif ($brand !== '' && str_contains($brand, $q)) {
            $score = 1;
        }

        $qSound = soundex($query);
        if ($qSound && $qSound === soundex((string) $med->primary_ingredient)) {
            $score = max($score, 3);
        }

        return $score;
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore();
        $user = auth()->user();

        $query = Consultation::query()
            ->whereNotNull('prescription_data')
            ->with(['patient', 'doctor', 'assignedPharmacist'])
            ->orderByDesc('created_at');

        $query->whereHas('doctor', function ($q) use ($store) {
            $q->where('hospital_admin_id', $store->hospital_admin_id);
        });

        if ($user->isPharmacist()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('pharmacist_id')
                    ->orWhere('pharmacist_id', $user->id);
            });
        }

        $consultations = $query->paginate(25)->withQueryString();

        $orders = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->whereIn('consultation_id', $consultations->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('consultation_id');

        $orderByConsultation = $orders->map(function ($ordersForConsultation) {
            return $ordersForConsultation->firstWhere('status', 'open') ?: $ordersForConsultation->first();
        });

        return view('pharmacy.dispense.index', compact('store', 'consultations', 'orderByConsultation'));
    }

    public function show(Consultation $consultation)
    {
        $store = $this->resolveStore();
        $this->authorizeConsultation($store, $consultation);

        $ordersForConsultation = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->where('consultation_id', $consultation->id)
            ->with(['items', 'items.batch'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->get();

        $order = $ordersForConsultation->first();

        $pData = $consultation->prescription_data;
        if (is_string($pData)) {
            $pData = json_decode($pData, true);
        }
        $pData = is_array($pData) ? $pData : [];

        $medicineNameSet = [];
        foreach (($pData['medicines'] ?? []) as $m) {
            if (!empty($m['name'])) {
                $medicineNameSet[] = (string) $m['name'];
            }
        }

        $batchesByMedicineName = [];
        if (!empty($medicineNameSet)) {
            $medicines = Medicine::whereIn('name', array_unique($medicineNameSet))->get()->keyBy('name');
            foreach ($medicines as $name => $med) {
                $batchesByMedicineName[$name] = StockBatch::where('pharmacy_store_id', $store->id)
                    ->where('medicine_id', $med->id)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderByRaw('expiry_date IS NULL, expiry_date asc')
                    ->get();
            }
        }

        $fulfillmentStatuses = $consultation->prescriptionFulfillments()
            ->pluck('status', 'medicine_name')
            ->toArray();

        $hasOpenOrder = $ordersForConsultation->contains(fn (DispenseOrder $o) => $o->status === 'open');
        $existingMedicineKeys = [];
        foreach ($ordersForConsultation as $o) {
            foreach ($o->items as $it) {
                $k = $this->normalizeSearchToken((string) $it->medicine_name);
                if ($k !== '') {
                    $existingMedicineKeys[$k] = true;
                }
            }
        }

        $rxMedicineKeys = [];
        foreach (($pData['medicines'] ?? []) as $m) {
            $name = is_array($m) ? (string) ($m['name'] ?? '') : '';
            $k = $this->normalizeSearchToken($name);
            if ($k !== '') {
                $rxMedicineKeys[$k] = true;
            }
        }

        $canCreateOrder = !$hasOpenOrder && !empty(array_diff(array_keys($rxMedicineKeys), array_keys($existingMedicineKeys)));

        return view('pharmacy.dispense.show', compact('store', 'consultation', 'pData', 'order', 'batchesByMedicineName', 'fulfillmentStatuses', 'canCreateOrder'));
    }

    public function stockBatches(Request $request)
    {
        $store = $this->resolveStore();

        // Support searching by generic name (from prescription) or specific medicine_id
        $medicineId = $request->input('medicine_id');
        $medicineName = $request->input('medicine_name');
        $searchBy = trim((string) $request->input('search_by', 'all'));
        
        $medicines = collect();

        // 1. Find Medicines matching the query
        if ($medicineId) {
            $med = Medicine::find($medicineId);
            if ($med) $medicines->push($med);
        } elseif ($medicineName) {
            $medicineName = trim((string) $medicineName);
            $like = '%' . $medicineName . '%';
            $driver = DB::getDriverName();

            $inStockMedicineIds = StockBatch::query()
                ->where('pharmacy_store_id', $store->id)
                ->where('quantity_on_hand', '>', 0)
                ->distinct()
                ->pluck('medicine_id');

            $inStockMedicines = Medicine::query()
                ->whereIn('id', $inStockMedicineIds)
                ->search($medicineName, $searchBy)
                ->limit(1000)
                ->get();

            $medicines = Medicine::query()
                ->search($medicineName, $searchBy)
                ->limit(1500)
                ->get()
                ->merge($inStockMedicines)
                ->unique('id')
                ->values();
        }

        if ($medicines->isEmpty()) {
            return response()->json(['items' => []]);
        }

        $medIds = $medicines->pluck('id');

        $popularity = DispenseItem::whereIn('medicine_id', $medIds)
            ->where('status', 'dispensed')
            ->select('medicine_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('medicine_id')
            ->pluck('usage_count', 'medicine_id');

        $batches = StockBatch::where('pharmacy_store_id', $store->id)
            ->whereIn('medicine_id', $medIds)
            ->where('quantity_on_hand', '>', 0)
            ->orderByRaw('expiry_date is null')
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->with('medicine')
            ->get();

        $bestBatchByMedicine = $batches
            ->groupBy('medicine_id')
            ->map(function ($group) {
                return $group->first();
            });

        $inStockMedicineIds = $bestBatchByMedicine->keys();

        $sortedInStockMedicineIds = $inStockMedicineIds->values()->all();
        $inStockMeta = [];
        foreach ($sortedInStockMedicineIds as $id) {
            $batch = $bestBatchByMedicine->get($id);
            $med = $batch?->medicine;
            $inStockMeta[$id] = [
                'score' => $this->medicineMatchScore((string) $medicineName, $med),
                'pop' => (int) $popularity->get($id, 0),
                'brand' => (string) ($med?->brand_name ?? ''),
                'name' => (string) ($med?->name ?? ''),
            ];
        }
        usort($sortedInStockMedicineIds, function ($a, $b) use ($inStockMeta) {
            $aa = $inStockMeta[$a] ?? ['score' => 0, 'pop' => 0, 'brand' => '', 'name' => ''];
            $bb = $inStockMeta[$b] ?? ['score' => 0, 'pop' => 0, 'brand' => '', 'name' => ''];
            if ($aa['score'] !== $bb['score']) return $bb['score'] <=> $aa['score'];
            if ($aa['pop'] !== $bb['pop']) return $bb['pop'] <=> $aa['pop'];
            $c = strcmp($aa['brand'], $bb['brand']);
            if ($c !== 0) return $c;
            return strcmp($aa['name'], $bb['name']);
        });

        $results = [];

        foreach ($sortedInStockMedicineIds as $id) {
            if (count($results) >= 10) {
                break;
            }
            $b = $bestBatchByMedicine->get($id);
            if (!$b || !$b->medicine) {
                continue;
            }
            $med = $b->medicine;
            $gd = $med->generic_display;
            $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
            $composition = $compText ?: (($med->primary_ingredient ? ($med->primary_ingredient . ($med->primary_strength ? ' ' . $med->primary_strength : '')) : null));

            $results[] = [
                'type' => 'batch',
                'id' => $b->id,
                'medicine_id' => $med->id,
                'name' => $med->name . ($med->strength ? ' ' . $med->strength : ''),
                'brand_name' => $med->brand_name,
                'strength' => $med->strength,
                'composition' => $composition,
                'batch_no' => $b->batch_no,
                'expiry_date' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : null,
                'quantity_on_hand' => (int) $b->quantity_on_hand,
                'mrp' => $b->mrp,
                'sale_price' => $b->sale_price,
            ];
        }

        $remaining = 10 - count($results);

        if ($remaining > 0) {
            $outOfStockMedicines = $medicines
                ->reject(function ($m) use ($inStockMedicineIds) {
                    return $inStockMedicineIds->contains($m->id);
                })
                ->values()
                ->all();
            usort($outOfStockMedicines, function ($a, $b) use ($popularity, $medicineName) {
                $sa = $this->medicineMatchScore((string) $medicineName, $a);
                $sb = $this->medicineMatchScore((string) $medicineName, $b);
                if ($sa !== $sb) return $sb <=> $sa;
                $pa = (int) $popularity->get($a->id, 0);
                $pb = (int) $popularity->get($b->id, 0);
                if ($pa !== $pb) return $pb <=> $pa;
                $c = strcmp((string) ($a->brand_name ?? ''), (string) ($b->brand_name ?? ''));
                if ($c !== 0) return $c;
                return strcmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
            });
            $outOfStockMedicines = array_slice($outOfStockMedicines, 0, $remaining);

            foreach ($outOfStockMedicines as $med) {
                $gd = $med->generic_display;
                $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
                $composition = $compText ?: (($med->primary_ingredient ? ($med->primary_ingredient . ($med->primary_strength ? ' ' . $med->primary_strength : '')) : null));

                $results[] = [
                    'type' => 'medicine_no_stock',
                    'id' => null,
                    'medicine_id' => $med->id,
                    'name' => $med->name . ($med->strength ? ' ' . $med->strength : ''),
                    'brand_name' => $med->brand_name,
                    'strength' => $med->strength,
                    'composition' => $composition,
                    'batch_no' => null,
                    'expiry_date' => null,
                    'quantity_on_hand' => 0,
                    'mrp' => null,
                    'sale_price' => null,
                ];
            }
        }

        return response()->json(['items' => $results]);
    }

    public function createOrder(Request $request, Consultation $consultation)
    {
        $store = $this->resolveStore();
        $this->authorizeConsultation($store, $consultation);

        $existingOpen = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->where('consultation_id', $consultation->id)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        if ($existingOpen) {
            return redirect()->route('pharmacy.dispense.show', $consultation->id);
        }

        $existingOrders = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->where('consultation_id', $consultation->id)
            ->with('items')
            ->get();

        $existingMedicineKeys = [];
        foreach ($existingOrders as $o) {
            foreach ($o->items as $it) {
                $k = $this->normalizeSearchToken((string) $it->medicine_name);
                if ($k !== '') {
                    $existingMedicineKeys[$k] = true;
                }
            }
        }

        $pData = $consultation->prescription_data;
        if (is_string($pData)) {
            $pData = json_decode($pData, true);
        }
        $pData = is_array($pData) ? $pData : [];

        try {
            $order = DispenseOrder::create([
                'pharmacy_store_id' => $store->id,
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'doctor_id' => $consultation->doctor_id,
                'pharmacist_id' => auth()->id(),
                'status' => 'open',
            ]);
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (($e->getCode() === '23000' || $e->getCode() === 23000) && str_contains($msg, 'dispense_orders_pharmacy_store_id_consultation_id_unique')) {
                return redirect()
                    ->route('pharmacy.dispense.show', $consultation->id)
                    ->with('error', 'A dispense order already exists for this consultation. Please run pending database migrations to enable multiple invoices for extra medicines.');
            }
            throw $e;
        }

        $items = [];
        $addedKeys = [];
        foreach (($pData['medicines'] ?? []) as $m) {
            if (empty($m['name'])) {
                continue;
            }

            $name = trim((string) $m['name']);
            $key = $this->normalizeSearchToken($name);
            if ($key === '' || isset($existingMedicineKeys[$key]) || isset($addedKeys[$key])) {
                continue;
            }
            $addedKeys[$key] = true;
            $medicine = Medicine::where('name', $name)->first();

            $items[] = [
                'dispense_order_id' => $order->id,
                'medicine_id' => $medicine?->id,
                'medicine_name' => $name,
                'dosage' => $m['dosage'] ?? null,
                'frequency' => $m['frequency'] ?? null,
                'duration' => $m['duration'] ?? null,
                'instruction' => $m['instruction'] ?? null,
                'quantity' => 1,
                'dispensed_quantity' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($items)) {
            $order->delete();
            return redirect()->route('pharmacy.dispense.show', $consultation->id)->with('error', 'No new medicines to dispense.');
        }

        DispenseItem::insert($items);

        return redirect()->route('pharmacy.dispense.show', $consultation->id)->with('success', 'Dispense order created.');
    }

    public function dispenseItem(Request $request, DispenseItem $item)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'stock_batch_id' => ['required', 'exists:stock_batches,id'],
            'medicine_id' => ['nullable', 'exists:medicines,id'],
        ]);

        $store = $this->resolveStore();
        $order = DispenseOrder::with('consultation')->findOrFail($item->dispense_order_id);

        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $this->authorizeConsultation($store, $order->consultation);

        if ($order->status !== 'open') {
            return back()->with('error', 'Order is not open.');
        }

        DB::transaction(function () use ($validated, $store, $item) {
            $batch = StockBatch::lockForUpdate()->findOrFail($validated['stock_batch_id']);
            if ((int) $batch->pharmacy_store_id !== (int) $store->id) {
                abort(404);
            }
            if (!empty($validated['medicine_id']) && (int) $batch->medicine_id !== (int) $validated['medicine_id']) {
                abort(422, 'Selected batch does not match selected medicine.');
            }

            $qty = (int) $validated['quantity'];
            if ($batch->quantity_on_hand < $qty) {
                abort(422, 'Insufficient stock in batch.');
            }

            $batch->quantity_on_hand -= $qty;
            $batch->save();

            $item->stock_batch_id = $batch->id;
            $item->medicine_id = $batch->medicine_id;
            $item->dispensed_quantity = $qty;
            $item->status = 'dispensed';
            $item->save();

            StockMovement::create([
                'pharmacy_store_id' => $store->id,
                'medicine_id' => $item->medicine_id ?? $batch->medicine_id,
                'stock_batch_id' => $batch->id,
                'quantity' => -$qty,
                'movement_type' => 'out',
                'reference_type' => 'dispense_item',
                'reference_id' => $item->id,
                'user_id' => auth()->id(),
            ]);
        });

        $consultation = Consultation::find($order->consultation_id);
        PrescriptionFulfillment::updateOrCreate(
            ['consultation_id' => $consultation->id, 'medicine_name' => $item->medicine_name],
            ['status' => 'given', 'pharmacist_id' => auth()->id()]
        );

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'dispense_item',
            'entity_type' => 'dispense_item',
            'entity_id' => $item->id,
            'meta' => [
                'consultation_id' => (int) $order->consultation_id,
                'medicine_name' => $item->medicine_name,
                    'medicine_id' => $item->medicine_id,
                'quantity' => (int) $validated['quantity'],
                'stock_batch_id' => (int) $validated['stock_batch_id'],
            ],
        ]);

        return back()->with('success', 'Item dispensed.');
    }

    public function markNotGiven(Request $request, DispenseItem $item)
    {
        $store = $this->resolveStore();
        $order = DispenseOrder::with('consultation')->findOrFail($item->dispense_order_id);

        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $this->authorizeConsultation($store, $order->consultation);

        if ($order->status !== 'open') {
            return back()->with('error', 'Order is not open.');
        }

        $item->status = 'not_given';
        $item->dispensed_quantity = 0;
        $item->stock_batch_id = null;
        $item->save();

        PrescriptionFulfillment::updateOrCreate(
            ['consultation_id' => $order->consultation_id, 'medicine_name' => $item->medicine_name],
            ['status' => 'not_given', 'pharmacist_id' => auth()->id()]
        );

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'dispense_item_not_given',
            'entity_type' => 'dispense_item',
            'entity_id' => $item->id,
            'meta' => [
                'consultation_id' => (int) $order->consultation_id,
                'medicine_name' => $item->medicine_name,
            ],
        ]);

        return back()->with('success', 'Item marked not given.');
    }

    public function finalize(Request $request, DispenseOrder $order)
    {
        $store = $this->resolveStore();
        $order->load('consultation', 'items');

        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $this->authorizeConsultation($store, $order->consultation);

        if ($order->status !== 'open') {
            return back()->with('error', 'Order is not open.');
        }

        $order->status = 'dispensed';
        $order->dispensed_at = Carbon::now();
        $order->pharmacist_id = auth()->id();
        $order->save();

        $invoice = PharmacyInvoice::where('pharmacy_store_id', $store->id)
            ->where('dispense_order_id', $order->id)
            ->first();

        if (!$invoice) {
            $invoice = DB::transaction(function () use ($store, $order) {
                PharmacyStore::whereKey($store->id)->lockForUpdate()->first();
                $invoice = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $invoiceNo = $this->generateInvoiceNo($store->id);
                    try {
                        $invoice = PharmacyInvoice::create([
                            'pharmacy_store_id' => $store->id,
                            'dispense_order_id' => $order->id,
                            'invoice_no' => $invoiceNo,
                            'patient_id' => $order->patient_id,
                            'subtotal' => 0,
                            'discount' => 0,
                            'tax' => 0,
                            'total' => 0,
                            'paid_total' => 0,
                            'status' => 'unpaid',
                            'issued_at' => Carbon::now(),
                        ]);
                        break;
                    } catch (\Illuminate\Database\QueryException $e) {
                        $msg = $e->getMessage();
                        if (($e->getCode() === '23000' || $e->getCode() === 23000) && (str_contains($msg, 'invoice_no') || str_contains($msg, 'pharmacy_invoices_invoice_no_unique'))) {
                            continue;
                        }
                        throw $e;
                    }
                }

                if (!$invoice) {
                    throw new \RuntimeException('Failed to generate a unique invoice number.');
                }

                $subtotal = 0.0;

                foreach ($order->items as $it) {
                    if ($it->status !== 'dispensed' || $it->dispensed_quantity <= 0) {
                        continue;
                    }

                    $unitPrice = 0.0;
                    if (!empty($it->stock_batch_id)) {
                        $batch = StockBatch::find($it->stock_batch_id);
                        if ($batch && $batch->sale_price !== null) {
                            $unitPrice = (float) $batch->sale_price;
                        }
                    }

                    $lineTotal = round(((int) $it->dispensed_quantity) * $unitPrice, 2);
                    $subtotal += $lineTotal;

                    PharmacyInvoiceItem::create([
                        'pharmacy_invoice_id' => $invoice->id,
                        'medicine_id' => $it->medicine_id,
                        'medicine_name' => $it->medicine_name,
                        'quantity' => (int) $it->dispensed_quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'stock_batch_id' => $it->stock_batch_id,
                    ]);
                }

                $invoice->subtotal = round($subtotal, 2);
                $invoice->total = round($subtotal - (float) $invoice->discount + (float) $invoice->tax, 2);
                $invoice->save();

                return $invoice;
            });
        }

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'dispense_order_finalize',
            'entity_type' => 'dispense_order',
            'entity_id' => $order->id,
            'meta' => [
                'consultation_id' => (int) $order->consultation_id,
                'invoice_id' => (int) $invoice->id,
            ],
        ]);

        return redirect()->route('pharmacy.invoices.show', $invoice->id)->with('success', 'Dispense order finalized and invoice created.');
    }

    private function generateInvoiceNo(int $storeId): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'PH' . $storeId . '-' . $date . '-';

        $last = PharmacyInvoice::where('invoice_no', 'like', $prefix . '%')
            ->orderByDesc('invoice_no')
            ->first();

        $next = 1;
        if ($last) {
            $suffix = (string) substr($last->invoice_no, strlen($prefix));
            if (ctype_digit($suffix)) {
                $next = ((int) $suffix) + 1;
            }
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeConsultation(PharmacyStore $store, Consultation $consultation): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        $doctor = $consultation->doctor;

        if (!$doctor || empty($doctor->hospital_admin_id) || (int) $doctor->hospital_admin_id !== (int) $store->hospital_admin_id) {
            abort(403);
        }

        if ($user->isPharmacist() && !empty($consultation->pharmacist_id) && (int) $consultation->pharmacist_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->isPharmacist() && (Schema::hasColumn('users', 'hospital_admin_id') && (int) $user->hospital_admin_id !== (int) $store->hospital_admin_id)) {
            abort(403);
        }
    }
}
