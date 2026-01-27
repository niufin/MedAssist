<?php

namespace App\Http\Controllers\Pharmacy\Concerns;

use App\Models\PharmacyStore;
use App\Models\User;

trait ResolvesPharmacyStore
{
    protected function resolveStore(): PharmacyStore
    {
        $user = auth()->user();

        $hospitalId = null;

        if ($user->isHospitalAdmin()) {
            $hospitalId = $user->id;
        } elseif ($user->isSuperAdmin()) {
            $requested = request()->integer('hospital_admin_id') ?: null;
            $fromSession = session('active_hospital_admin_id');
            $hospitalId = $requested ?: $fromSession;

            if (empty($hospitalId)) {
                $hospitalId = PharmacyStore::query()->min('hospital_admin_id');
            }

            if (empty($hospitalId)) {
                $hospitalId = User::where('role', User::ROLE_HOSPITAL_ADMIN)->min('id');
            }

            if (empty($hospitalId)) {
                $hospitalId = $user->id;
            }

            session(['active_hospital_admin_id' => (int) $hospitalId]);
        } else {
            $hospitalId = $user->hospital_admin_id;
        }

        if (empty($hospitalId)) {
            abort(403);
        }

        return PharmacyStore::firstOrCreate(
            ['hospital_admin_id' => (int) $hospitalId],
            ['name' => 'Pharmacy']
        );
    }
}

