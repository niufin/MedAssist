<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\LabReport;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $moduleStats = [
            'patients' => [],
            'pharmacy' => [],
            'lab' => [],
            'interactive' => [],
        ];
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

                $consultationsScope = function ($q) use ($user, $doctorIds) {
                    $q->where(function ($qq) use ($user, $doctorIds) {
                        $qq->where('doctor_id', $user->id);
                        if ($doctorIds->isNotEmpty()) {
                            $qq->orWhereIn('doctor_id', $doctorIds);
                        }
                        $qq->orWhere(function ($w) use ($user) {
                            $w->whereNull('doctor_id')
                                ->whereNotNull('patient_id')
                                ->whereHas('patient', function ($p) use ($user) {
                                    $p->where('hospital_admin_id', $user->id);
                                });
                        });
                    });
                };

                $activeConsultations = Consultation::query()
                    ->where($consultationsScope)
                    ->where(function ($q) {
                        $q->whereNull('is_finalized')->orWhere('is_finalized', false);
                    })
                    ->count();

                $moduleStats['patients'] = [
                    'total' => (int) ($summary['patients'] ?? 0),
                    'active_consultations' => (int) $activeConsultations,
                ];

                $moduleStats['interactive'] = [
                    'active' => (int) $activeConsultations,
                    'total' => (int) ($summary['consultations'] ?? 0),
                ];

                $store = PharmacyStore::firstOrCreate(['hospital_admin_id' => $user->id], ['name' => 'Pharmacy']);
                $nearExpiryCutoff = Carbon::now()->addDays($store->near_expiry_days)->toDateString();

                $inStockMedicines = StockBatch::query()
                    ->where('pharmacy_store_id', $store->id)
                    ->where('quantity_on_hand', '>', 0)
                    ->distinct()
                    ->count('medicine_id');

                $nearExpiryMedicines = StockBatch::query()
                    ->where('pharmacy_store_id', $store->id)
                    ->where('quantity_on_hand', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', $nearExpiryCutoff)
                    ->distinct()
                    ->count('medicine_id');

                $lowStockMedicines = StockBatch::query()
                    ->where('pharmacy_store_id', $store->id)
                    ->where('quantity_on_hand', '>', 0)
                    ->groupBy('medicine_id')
                    ->havingRaw('SUM(quantity_on_hand) <= ?', [$store->low_stock_threshold])
                    ->pluck('medicine_id')
                    ->count();

                $moduleStats['pharmacy'] = [
                    'in_stock' => (int) $inStockMedicines,
                    'near_expiry' => (int) $nearExpiryMedicines,
                    'low_stock' => (int) $lowStockMedicines,
                    'near_expiry_cutoff' => $nearExpiryCutoff,
                ];

                $labReportsTotal = LabReport::query()
                    ->whereHas('consultation', $consultationsScope)
                    ->count();

                $labReportsRecent = LabReport::query()
                    ->whereHas('consultation', $consultationsScope)
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->count();

                $moduleStats['lab'] = [
                    'reports_total' => (int) $labReportsTotal,
                    'reports_7d' => (int) $labReportsRecent,
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
            'moduleStats' => $moduleStats,
            'recentConsultations' => $recentConsultations,
        ]);
    }
}
