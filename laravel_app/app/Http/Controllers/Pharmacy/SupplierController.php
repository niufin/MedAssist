<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\PharmacyStore;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ResolvesPharmacyStore;

    public function index()
    {
        $store = $this->resolveStore();
        $suppliers = Supplier::where('pharmacy_store_id', $store->id)->orderBy('name')->get();
        return view('pharmacy.purchases.suppliers.index', compact('store', 'suppliers'));
    }

    public function create()
    {
        $store = $this->resolveStore();
        return view('pharmacy.purchases.suppliers.create', compact('store'));
    }

    public function store(Request $request)
    {
        $store = $this->resolveStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['pharmacy_store_id'] = $store->id;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $supplier = Supplier::create($validated);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'supplier_create',
            'entity_type' => 'supplier',
            'entity_id' => $supplier->id,
            'meta' => [
                'name' => $supplier->name,
            ],
        ]);

        return redirect()->route('pharmacy.suppliers.index')->with('success', 'Supplier added.');
    }

    public function edit(Supplier $supplier)
    {
        $store = $this->resolveStore();
        if ((int) $supplier->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        return view('pharmacy.purchases.suppliers.edit', compact('store', 'supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $store = $this->resolveStore();
        if ((int) $supplier->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $supplier->update($validated);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'supplier_update',
            'entity_type' => 'supplier',
            'entity_id' => $supplier->id,
            'meta' => [
                'name' => $supplier->name,
            ],
        ]);

        return redirect()->route('pharmacy.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $store = $this->resolveStore();
        if ((int) $supplier->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $supplierId = $supplier->id;
        $name = $supplier->name;
        $supplier->delete();

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'supplier_delete',
            'entity_type' => 'supplier',
            'entity_id' => $supplierId,
            'meta' => [
                'name' => $name,
            ],
        ]);
        return redirect()->route('pharmacy.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
