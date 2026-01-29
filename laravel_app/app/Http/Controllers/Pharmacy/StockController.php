<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResolvesPharmacyStore;

    public function createIn(Request $request)
    {
        $store = $this->resolveStore();
        $search = trim((string) $request->query('q', ''));
        $searchBy = trim((string) $request->query('search_by', 'all'));
        $medicineId = $request->query('medicine_id');

        $medicine = null;
        if (!empty($medicineId)) {
            $medicine = Medicine::find($medicineId);
        }

        $medicines = [];
        if ($search !== '') {
            $medicines = Medicine::where('is_active', true)
                ->where('is_discontinued', false)
                ->with(['manufacturer', 'packages'])
                ->search($search, $searchBy)
                ->orderBy('name')
                ->orderBy('strength')
                ->limit(50)
                ->get();
        }

        return view('pharmacy.stock.in', compact('store', 'search', 'searchBy', 'medicines', 'medicine'));
    }

    public function storeIn(Request $request)
    {
        $store = $this->resolveStore();

        $validated = $request->validate([
            'medicine_id' => ['required', 'exists:medicines,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'rack_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $batch = StockBatch::where('pharmacy_store_id', $store->id)
            ->where('medicine_id', $validated['medicine_id'])
            ->where('batch_no', $validated['batch_no'] ?? null)
            ->where('expiry_date', $validated['expiry_date'] ?? null)
            ->where('mrp', $validated['mrp'] ?? null)
            ->where('purchase_price', $validated['purchase_price'] ?? null)
            ->where('sale_price', $validated['sale_price'] ?? null)
            ->where('rack_location', $validated['rack_location'] ?? null)
            ->first();

        if (!$batch) {
            $batch = StockBatch::create([
                'pharmacy_store_id' => $store->id,
                'medicine_id' => $validated['medicine_id'],
                'batch_no' => $validated['batch_no'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'mrp' => $validated['mrp'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'sale_price' => $validated['sale_price'] ?? null,
                'rack_location' => $validated['rack_location'] ?? null,
                'quantity_on_hand' => 0,
            ]);
        }

        $batch->quantity_on_hand += (int) $validated['quantity'];
        $batch->save();

        StockMovement::create([
            'pharmacy_store_id' => $store->id,
            'medicine_id' => $validated['medicine_id'],
            'stock_batch_id' => $batch->id,
            'quantity' => (int) $validated['quantity'],
            'movement_type' => 'in',
            'user_id' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'stock_in',
            'entity_type' => 'stock_batch',
            'entity_id' => $batch->id,
            'meta' => [
                'medicine_id' => (int) $validated['medicine_id'],
                'quantity' => (int) $validated['quantity'],
            ],
        ]);

        return redirect()->route('pharmacy.inventory.show', $validated['medicine_id'])->with('success', 'Stock added.');
    }

    public function editAdjust(StockBatch $batch)
    {
        $store = $this->resolveStore();
        if ((int) $batch->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $batch->load('medicine');

        return view('pharmacy.stock.adjust', compact('store', 'batch'));
    }

    public function updateAdjust(Request $request, StockBatch $batch)
    {
        $store = $this->resolveStore();
        if ((int) $batch->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'delta' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $newQty = $batch->quantity_on_hand + (int) $validated['delta'];
        if ($newQty < 0) {
            return back()->with('error', 'Adjustment would make stock negative.');
        }

        $batch->quantity_on_hand = $newQty;
        $batch->save();

        StockMovement::create([
            'pharmacy_store_id' => $store->id,
            'medicine_id' => $batch->medicine_id,
            'stock_batch_id' => $batch->id,
            'quantity' => (int) $validated['delta'],
            'movement_type' => 'adjustment',
            'user_id' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'stock_adjust',
            'entity_type' => 'stock_batch',
            'entity_id' => $batch->id,
            'meta' => [
                'medicine_id' => (int) $batch->medicine_id,
                'delta' => (int) $validated['delta'],
            ],
        ]);

        return redirect()->route('pharmacy.inventory.show', $batch->medicine_id)->with('success', 'Stock adjusted.');
    }
}
