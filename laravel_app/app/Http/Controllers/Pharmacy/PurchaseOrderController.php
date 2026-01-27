<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    use ResolvesPharmacyStore;

    public function index()
    {
        $store = $this->resolveStore();
        $orders = PurchaseOrder::where('pharmacy_store_id', $store->id)
            ->with('supplier')
            ->orderByDesc('id')
            ->paginate(25);

        return view('pharmacy.purchases.orders.index', compact('store', 'orders'));
    }

    public function create()
    {
        $store = $this->resolveStore();
        $suppliers = Supplier::where('pharmacy_store_id', $store->id)->where('is_active', true)->orderBy('name')->get();
        return view('pharmacy.purchases.orders.create', compact('store', 'suppliers'));
    }

    public function store(Request $request)
    {
        $store = $this->resolveStore();

        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $poNo = $this->generatePoNo($store->id);

        $order = PurchaseOrder::create([
            'pharmacy_store_id' => $store->id,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'po_no' => $poNo,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'purchase_order_create',
            'entity_type' => 'purchase_order',
            'entity_id' => $order->id,
            'meta' => [
                'po_no' => $order->po_no,
                'supplier_id' => $order->supplier_id,
            ],
        ]);

        return redirect()->route('pharmacy.purchases.orders.show', $order->id)->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $order, Request $request)
    {
        $store = $this->resolveStore();
        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $order->load(['supplier', 'items']);

        $search = trim((string) $request->query('q', ''));
        $searchBy = trim((string) $request->query('search_by', 'all'));
        $medicines = [];
        if ($search !== '') {
            $medicines = Medicine::where('is_active', true)
                ->where('is_discontinued', false)
                ->with(['manufacturer', 'packages'])
                ->search($search, $searchBy)
                ->orderBy('name')
                ->orderBy('strength')
                ->limit(30)
                ->get();
        }

        return view('pharmacy.purchases.orders.show', compact('store', 'order', 'search', 'searchBy', 'medicines'));
    }

    public function addItem(Request $request, PurchaseOrder $order)
    {
        $store = $this->resolveStore();
        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be edited.');
        }

        $validated = $request->validate([
            'medicine_id' => ['nullable', 'exists:medicines,id'],
            'medicine_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $medicine = null;
        if (!empty($validated['medicine_id'])) {
            $medicine = Medicine::find($validated['medicine_id']);
        }

        $name = $medicine?->name ?? ($validated['medicine_name'] ?? null);
        if (empty($name)) {
            return back()->with('error', 'Medicine is required.');
        }

        $unitCost = isset($validated['unit_cost']) ? (float) $validated['unit_cost'] : 0.0;
        $qty = (int) $validated['quantity'];
        $lineTotal = round($qty * $unitCost, 2);

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'medicine_id' => $medicine?->id,
            'medicine_name' => $name,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'line_total' => $lineTotal,
        ]);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'purchase_order_add_item',
            'entity_type' => 'purchase_order',
            'entity_id' => $order->id,
            'meta' => [
                'medicine_id' => $medicine?->id,
                'medicine_name' => $name,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
            ],
        ]);

        return back()->with('success', 'Item added.');
    }

    public function submit(Request $request, PurchaseOrder $order)
    {
        $store = $this->resolveStore();
        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        if ($order->status !== 'draft') {
            return back()->with('error', 'Order is not in draft.');
        }

        if ($order->items()->count() === 0) {
            return back()->with('error', 'Add at least one item.');
        }

        $order->status = 'ordered';
        $order->ordered_at = Carbon::now();
        $order->save();

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'purchase_order_submit',
            'entity_type' => 'purchase_order',
            'entity_id' => $order->id,
            'meta' => [
                'po_no' => $order->po_no,
            ],
        ]);

        return back()->with('success', 'Order submitted.');
    }

    public function receiveForm(PurchaseOrder $order)
    {
        $store = $this->resolveStore();
        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $order->load(['supplier', 'items']);

        return view('pharmacy.purchases.orders.receive', compact('store', 'order'));
    }

    public function receive(Request $request, PurchaseOrder $order)
    {
        $store = $this->resolveStore();
        if ((int) $order->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $order->load('items');

        if (!in_array($order->status, ['ordered', 'draft'], true)) {
            return back()->with('error', 'Order cannot be received.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0'],
            'items.*.batch_no' => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.mrp' => ['nullable', 'numeric', 'min:0'],
            'items.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $grn = DB::transaction(function () use ($store, $order, $validated) {
            $grnNo = $this->generateGrnNo($store->id);

            $grn = GoodsReceipt::create([
                'pharmacy_store_id' => $store->id,
                'purchase_order_id' => $order->id,
                'grn_no' => $grnNo,
                'status' => 'received',
                'received_at' => Carbon::now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($order->items as $poi) {
                $row = $validated['items'][$poi->id] ?? null;
                if (!$row) {
                    continue;
                }

                $qty = (int) ($row['quantity_received'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $medicineId = $poi->medicine_id;
                if (empty($medicineId)) {
                    $name = trim((string) $poi->medicine_name);
                    if ($name === '') {
                        throw new \RuntimeException('Purchase order item is missing medicine.');
                    }

                    $medicine = Medicine::where('name', $name)->orderBy('id')->first();
                    if (!$medicine) {
                        $medicine = Medicine::create([
                            'name' => $name,
                            'strength' => null,
                            'type' => null,
                            'therapeutic_class' => null,
                            'is_active' => true,
                        ]);
                    }

                    $medicineId = $medicine->id;
                    $poi->medicine_id = $medicineId;
                    $poi->save();
                }

                $batch = StockBatch::where('pharmacy_store_id', $store->id)
                    ->where('medicine_id', $medicineId)
                    ->where('batch_no', $row['batch_no'] ?? null)
                    ->where('expiry_date', $row['expiry_date'] ?? null)
                    ->where('mrp', $row['mrp'] ?? null)
                    ->where('purchase_price', $row['purchase_price'] ?? null)
                    ->where('sale_price', $row['sale_price'] ?? null)
                    ->first();

                if (!$batch) {
                    $batch = StockBatch::create([
                        'pharmacy_store_id' => $store->id,
                        'medicine_id' => $medicineId,
                        'batch_no' => $row['batch_no'] ?? null,
                        'expiry_date' => $row['expiry_date'] ?? null,
                        'mrp' => $row['mrp'] ?? null,
                        'purchase_price' => $row['purchase_price'] ?? null,
                        'sale_price' => $row['sale_price'] ?? null,
                        'quantity_on_hand' => 0,
                    ]);
                }

                $batch->quantity_on_hand += $qty;
                $batch->save();

                $gri = GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'purchase_order_item_id' => $poi->id,
                    'medicine_id' => $medicineId,
                    'medicine_name' => $poi->medicine_name,
                    'quantity_received' => $qty,
                    'batch_no' => $row['batch_no'] ?? null,
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'mrp' => $row['mrp'] ?? null,
                    'purchase_price' => $row['purchase_price'] ?? null,
                    'sale_price' => $row['sale_price'] ?? null,
                    'stock_batch_id' => $batch->id,
                ]);

                StockMovement::create([
                    'pharmacy_store_id' => $store->id,
                    'medicine_id' => $medicineId,
                    'stock_batch_id' => $batch->id,
                    'quantity' => $qty,
                    'movement_type' => 'purchase_in',
                    'reference_type' => 'goods_receipt_item',
                    'reference_id' => $gri->id,
                    'user_id' => auth()->id(),
                ]);
            }

            return $grn;
            });
        } catch (\Throwable $e) {
            $message = $e instanceof \RuntimeException
                ? ($e->getMessage() ?: 'Failed to receive goods.')
                : 'Failed to receive goods.';
            return back()->with('error', $message);
        }

        $order->status = 'received';
        $order->save();

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'purchase_order_receive',
            'entity_type' => 'purchase_order',
            'entity_id' => $order->id,
            'meta' => [
                'po_no' => $order->po_no,
                'grn_no' => $grn->grn_no,
            ],
        ]);

        return redirect()->route('pharmacy.inventory.index')->with('success', 'Goods received and stock updated.');
    }

    private function generatePoNo(int $storeId): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'PO' . $storeId . '-' . $date . '-';
        $last = PurchaseOrder::where('po_no', 'like', $prefix . '%')->orderByDesc('po_no')->first();
        $next = 1;
        if ($last) {
            $suffix = (string) substr($last->po_no, strlen($prefix));
            if (ctype_digit($suffix)) {
                $next = ((int) $suffix) + 1;
            }
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateGrnNo(int $storeId): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'GRN' . $storeId . '-' . $date . '-';
        $last = GoodsReceipt::where('grn_no', 'like', $prefix . '%')->orderByDesc('grn_no')->first();
        $next = 1;
        if ($last) {
            $suffix = (string) substr($last->grn_no, strlen($prefix));
            if (ctype_digit($suffix)) {
                $next = ((int) $suffix) + 1;
            }
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

}
