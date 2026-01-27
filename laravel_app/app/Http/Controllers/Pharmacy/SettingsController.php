<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\PharmacyStore;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ResolvesPharmacyStore;

    public function edit()
    {
        $store = $this->resolveStore();
        return view('pharmacy.settings.edit', compact('store'));
    }

    public function update(Request $request)
    {
        $store = $this->resolveStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'near_expiry_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $store->update($validated);

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'pharmacy_settings_update',
            'entity_type' => 'pharmacy_store',
            'entity_id' => $store->id,
            'meta' => [
                'low_stock_threshold' => (int) $store->low_stock_threshold,
                'near_expiry_days' => (int) $store->near_expiry_days,
            ],
        ]);

        return back()->with('success', 'Settings updated.');
    }
}
