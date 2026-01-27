<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStore;
use App\Models\User;
use Illuminate\Http\Request;

class PharmacyStoreController extends Controller
{
    public function index()
    {
        $stores = PharmacyStore::with('hospitalAdmin')
            ->orderBy('name')
            ->get();

        return view('admin.pharmacies.index', compact('stores'));
    }

    public function create()
    {
        $hospitals = User::where('role', User::ROLE_HOSPITAL_ADMIN)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pharmacies.create', compact('hospitals'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hospital_admin_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0|max:100000',
            'near_expiry_days' => 'required|integer|min:0|max:3650',
        ]);

        $hospital = User::where('role', User::ROLE_HOSPITAL_ADMIN)
            ->where('id', $validated['hospital_admin_id'])
            ->first();

        if (!$hospital) {
            return back()->with('error', 'Selected Hospital is invalid.')->withInput();
        }

        $existing = PharmacyStore::where('hospital_admin_id', $hospital->id)->first();
        if ($existing) {
            return redirect()->route('admin.pharmacies.edit', $existing)->with('status', 'Pharmacy already exists for this hospital. You can edit it here.');
        }

        PharmacyStore::create([
            'hospital_admin_id' => $hospital->id,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'low_stock_threshold' => $validated['low_stock_threshold'],
            'near_expiry_days' => $validated['near_expiry_days'],
        ]);

        return redirect()->route('admin.pharmacies.index')->with('success', 'Pharmacy created successfully.');
    }

    public function edit(PharmacyStore $pharmacy)
    {
        $hospitals = User::where('role', User::ROLE_HOSPITAL_ADMIN)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pharmacies.edit', ['store' => $pharmacy, 'hospitals' => $hospitals]);
    }

    public function update(Request $request, PharmacyStore $pharmacy)
    {
        $validated = $request->validate([
            'hospital_admin_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0|max:100000',
            'near_expiry_days' => 'required|integer|min:0|max:3650',
        ]);

        $hospital = User::where('role', User::ROLE_HOSPITAL_ADMIN)
            ->where('id', $validated['hospital_admin_id'])
            ->first();

        if (!$hospital) {
            return back()->with('error', 'Selected Hospital is invalid.')->withInput();
        }

        $conflict = PharmacyStore::where('hospital_admin_id', $hospital->id)
            ->where('id', '!=', $pharmacy->id)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'That hospital already has a pharmacy assigned.')->withInput();
        }

        $pharmacy->update([
            'hospital_admin_id' => $hospital->id,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'low_stock_threshold' => $validated['low_stock_threshold'],
            'near_expiry_days' => $validated['near_expiry_days'],
        ]);

        return redirect()->route('admin.pharmacies.index')->with('success', 'Pharmacy updated successfully.');
    }

    public function destroy(PharmacyStore $pharmacy)
    {
        $pharmacy->delete();

        return redirect()->route('admin.pharmacies.index')->with('success', 'Pharmacy deleted successfully.');
    }
}

