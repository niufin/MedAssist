<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContextController extends Controller
{
    public function setActiveHospital(Request $request)
    {
        $validated = $request->validate([
            'hospital_admin_id' => ['required', 'integer', 'min:1'],
            'redirect' => ['nullable', 'string'],
        ]);

        session(['active_hospital_admin_id' => (int) $validated['hospital_admin_id']]);

        $redirect = $validated['redirect'] ?? route('pharmacy.inventory.index');
        return redirect($redirect);
    }
}

