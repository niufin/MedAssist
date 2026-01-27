<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HospitalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->isHospitalAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $summary = null;
        $recentConsultations = collect();

        try {
            if (Schema::hasColumn('users', 'hospital_admin_id')) {
                $doctorIds = $user->hospitalUsers()
                    ->where('role', User::ROLE_DOCTOR)
                    ->pluck('id');

                $summary = [
                    'doctors' => $doctorIds->count(),
                    'patients' => $user->hospitalUsers()->where('role', User::ROLE_PATIENT)->count(),
                    'pharmacists' => $user->hospitalUsers()->where('role', User::ROLE_PHARMACIST)->count(),
                    'lab_assistants' => $user->hospitalUsers()->where('role', User::ROLE_LAB_ASSISTANT)->count(),
                    'consultations' => Consultation::where(function ($q) use ($user, $doctorIds) {
                        $q->where('doctor_id', $user->id);
                        if ($doctorIds->isNotEmpty()) {
                            $q->orWhereIn('doctor_id', $doctorIds);
                        }
                        $q->orWhere(function ($w) use ($user) {
                            $w->whereNull('doctor_id')
                                ->whereNotNull('patient_id')
                                ->whereHas('patient', function ($p) use ($user) {
                                    $p->where('hospital_admin_id', $user->id);
                                });
                        });
                    })->count(),
                ];

                $recentConsultations = Consultation::with(['patient', 'doctor'])
                    ->where(function ($q) use ($user, $doctorIds) {
                        $q->where('doctor_id', $user->id);
                        if ($doctorIds->isNotEmpty()) {
                            $q->orWhereIn('doctor_id', $doctorIds);
                        }
                        $q->orWhere(function ($w) use ($user) {
                            $w->whereNull('doctor_id')
                                ->whereNotNull('patient_id')
                                ->whereHas('patient', function ($p) use ($user) {
                                    $p->where('hospital_admin_id', $user->id);
                                });
                        });
                    })
                    ->latest()
                    ->limit(15)
                    ->get();
            }
        } catch (\Throwable $e) {
            $summary = null;
            $recentConsultations = collect();
        }

        return view('hospital.dashboard', [
            'summary' => $summary,
            'recentConsultations' => $recentConsultations,
        ]);
    }
}

