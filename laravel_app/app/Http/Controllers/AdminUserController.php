<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AdminUserController extends Controller
{
    private function stripUnavailableUserColumns(array $payload): array
    {
        if (!Schema::hasColumn('users', 'designation')) {
            unset($payload['designation']);
        }
        if (!Schema::hasColumn('users', 'additional_qualifications')) {
            unset($payload['additional_qualifications']);
        }
        return $payload;
    }

    public function index(Request $request)
    {
        $current = auth()->user();
        $role = strtolower(trim((string) $request->query('role', '')));
        $q = trim((string) $request->query('q', ''));

        $usersQuery = User::query();
        if ($current && $current->isHospitalAdmin()) {
            $usersQuery->where('hospital_admin_id', $current->id);
        }

        if ($role !== '') {
            $usersQuery->where('role', $role);
        }

        if ($q !== '') {
            $usersQuery->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('contact_number', 'like', '%' . $q . '%')
                    ->orWhere('mrn', 'like', '%' . $q . '%')
                    ->orWhere('license_number', 'like', '%' . $q . '%');
            });
        }

        $users = $usersQuery->orderByDesc('id')->paginate(50)->withQueryString();
        $hospitalAdmins = User::where('role', User::ROLE_HOSPITAL_ADMIN)->get();
        return view('admin.users.index', compact('users', 'hospitalAdmins', 'role', 'q'));
    }

    public function create()
    {
        $hospitalAdmins = User::where('role', User::ROLE_HOSPITAL_ADMIN)->get();
        $role = strtolower(trim((string) request()->query('role', '')));
        return view('admin.users.create', compact('hospitalAdmins', 'role'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string',
            'hospital_admin_id' => 'nullable|integer',
            'degrees' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'additional_qualifications' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'medical_center_name' => 'nullable|string|max:255',
        ]);

        $current = auth()->user();

        if ($current && $current->isHospitalAdmin()) {
            if (!in_array($validated['role'], [User::ROLE_DOCTOR, User::ROLE_PHARMACIST, User::ROLE_LAB_ASSISTANT, User::ROLE_PATIENT], true)) {
                return redirect()->route('admin.users.index')->with('error', 'Hospital Admins can only create Doctors, Pharmacists, Lab Assistants, and Patients.');
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => User::STATUS_ACTIVE,
            'degrees' => $validated['degrees'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'additional_qualifications' => $validated['additional_qualifications'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'medical_center_name' => $validated['medical_center_name'] ?? null,
        ];

        if ($current && $current->isHospitalAdmin()) {
            $data['hospital_admin_id'] = $current->id;
        } else {
            if (!empty($validated['hospital_admin_id'])) {
                $ha = User::find($validated['hospital_admin_id']);
                if (!$ha || !$ha->isHospitalAdmin()) {
                    return back()->with('error', 'Selected Hospital Admin is invalid.');
                }
                if (!in_array($validated['role'], [User::ROLE_DOCTOR, User::ROLE_PHARMACIST, User::ROLE_LAB_ASSISTANT, User::ROLE_PATIENT], true)) {
                    $data['hospital_admin_id'] = null;
                } else {
                    $data['hospital_admin_id'] = $ha->id;
                }
            }
        }

        if ($validated['role'] === User::ROLE_PATIENT) {
            $mrn = null;
            do {
                $mrn = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
            } while (User::where('mrn', $mrn)->exists());
            $data['mrn'] = $mrn;
        }

        $data = $this->stripUnavailableUserColumns($data);
        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot edit a Super Admin.');
        }
        if (auth()->user()->isHospitalAdmin() && $user->hospital_admin_id !== auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot edit users from another hospital.');
        }
        $hospitalAdmins = User::where('role', User::ROLE_HOSPITAL_ADMIN)->get();
        return view('admin.users.edit', compact('user', 'hospitalAdmins'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'You cannot edit a Super Admin.');
        }

        if (auth()->user()->isHospitalAdmin() && $user->hospital_admin_id !== auth()->id()) {
            return back()->with('error', 'You cannot edit users from another hospital.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'status' => 'required|string',
            'hospital_admin_id' => 'nullable|integer',
            'degrees' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'additional_qualifications' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'medical_center_name' => 'nullable|string|max:255',
        ]);

        if (auth()->user()->isHospitalAdmin()) {
            if (!in_array($validated['role'], [User::ROLE_DOCTOR, User::ROLE_PHARMACIST, User::ROLE_LAB_ASSISTANT, User::ROLE_PATIENT], true)) {
                return back()->with('error', 'Hospital Admins can only assign Doctor, Pharmacist, Lab Assistant, or Patient roles.');
            }
        }

        $payload = $validated;
        if (auth()->user()->isHospitalAdmin()) {
            $payload['hospital_admin_id'] = auth()->id();
        } else {
            if (array_key_exists('hospital_admin_id', $validated)) {
                $val = $validated['hospital_admin_id'];
                if ($val) {
                    $ha = User::find($val);
                    if (!$ha || !$ha->isHospitalAdmin()) {
                        return back()->with('error', 'Selected Hospital Admin is invalid.');
                    }
                    if (!in_array($validated['role'], [User::ROLE_DOCTOR, User::ROLE_PHARMACIST, User::ROLE_LAB_ASSISTANT, User::ROLE_PATIENT], true)) {
                        $payload['hospital_admin_id'] = null;
                    } else {
                        $payload['hospital_admin_id'] = $ha->id;
                    }
                } else {
                    $payload['hospital_admin_id'] = null;
                }
            }
        }

        $payload = $this->stripUnavailableUserColumns($payload);
        $user->update($payload);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function approve(User $user)
    {
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'You cannot edit a Super Admin.');
        }

        $user->update(['status' => User::STATUS_ACTIVE]);
        return back()->with('success', 'User approved.');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'You cannot delete a Super Admin.');
        }

        if (auth()->user()->isHospitalAdmin() && $user->hospital_admin_id !== auth()->id()) {
            return back()->with('error', 'You cannot delete users from another hospital.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }
}
