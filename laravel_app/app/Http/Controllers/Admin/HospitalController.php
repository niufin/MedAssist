<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HospitalController extends Controller
{
    public function index()
    {
        $hospitals = User::where('role', User::ROLE_HOSPITAL_ADMIN)
            ->withCount('hospitalUsers')
            ->orderBy('name')
            ->get();

        return view('admin.hospitals.index', compact('hospitals'));
    }

    public function create()
    {
        return view('admin.hospitals.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'nullable|string|max:50',
            'clinic_address' => 'nullable|string|max:500',
            'clinic_contact_number' => 'nullable|string|max:50',
            'clinic_email' => 'nullable|string|email|max:255',
            'clinic_registration_number' => 'nullable|string|max:255',
            'clinic_gstin' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_HOSPITAL_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'contact_number' => $validated['contact_number'] ?? null,
            'clinic_address' => $validated['clinic_address'] ?? null,
            'clinic_contact_number' => $validated['clinic_contact_number'] ?? null,
            'clinic_email' => $validated['clinic_email'] ?? null,
            'clinic_registration_number' => $validated['clinic_registration_number'] ?? null,
            'clinic_gstin' => $validated['clinic_gstin'] ?? null,
        ]);

        return redirect()->route('admin.hospitals.index')->with('success', 'Hospital created successfully.');
    }

    public function edit(User $hospital)
    {
        if (!$hospital->isHospitalAdmin()) {
            abort(404);
        }

        return view('admin.hospitals.edit', compact('hospital'));
    }

    public function update(Request $request, User $hospital)
    {
        if (!$hospital->isHospitalAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $hospital->id,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|string',
            'contact_number' => 'nullable|string|max:50',
            'clinic_address' => 'nullable|string|max:500',
            'clinic_contact_number' => 'nullable|string|max:50',
            'clinic_email' => 'nullable|string|email|max:255',
            'clinic_registration_number' => 'nullable|string|max:255',
            'clinic_gstin' => 'nullable|string|max:255',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'contact_number' => $validated['contact_number'] ?? null,
            'clinic_address' => $validated['clinic_address'] ?? null,
            'clinic_contact_number' => $validated['clinic_contact_number'] ?? null,
            'clinic_email' => $validated['clinic_email'] ?? null,
            'clinic_registration_number' => $validated['clinic_registration_number'] ?? null,
            'clinic_gstin' => $validated['clinic_gstin'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $hospital->update($payload);

        return redirect()->route('admin.hospitals.index')->with('success', 'Hospital updated successfully.');
    }

    public function destroy(User $hospital)
    {
        if (!$hospital->isHospitalAdmin()) {
            abort(404);
        }

        $hospital->delete();

        return redirect()->route('admin.hospitals.index')->with('success', 'Hospital deleted successfully.');
    }
}
