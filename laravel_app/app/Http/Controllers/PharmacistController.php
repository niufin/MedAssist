<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\Consultation;
use App\Models\PrescriptionFulfillment;
use App\Models\StockBatch;
use App\Models\User;
use App\Notifications\MedicineFulfilled;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PharmacistController extends Controller
{
    use ResolvesPharmacyStore;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Consultation::query()
            ->whereNotNull('prescription_data')
            ->with(['patient', 'doctor', 'prescriptionFulfillments'])
            ->orderByDesc('created_at');

        if ($user && $user->isSuperAdmin()) {
        } elseif ($user && $user->isPharmacist()) {
            if (!Schema::hasColumn('users', 'hospital_admin_id') || empty($user->hospital_admin_id)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($user) {
                    $q->whereHas('doctor', function ($q) use ($user) {
                        $q->where('hospital_admin_id', $user->hospital_admin_id);
                    })->orWhereHas('patient', function ($q) use ($user) {
                        $q->where('hospital_admin_id', $user->hospital_admin_id);
                    });
                });

                $query->where(function ($q) use ($user) {
                    $q->whereNull('pharmacist_id')
                        ->orWhere('pharmacist_id', $user->id);
                });
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $consultations = $query->get();
            
        $selectedConsultation = null;
        if ($request->has('id')) {
            $selectedConsultation = $consultations->firstWhere('id', $request->id);
        }

        $fulfillmentStatuses = [];
        if ($selectedConsultation) {
            $fulfillmentStatuses = $selectedConsultation->prescriptionFulfillments
                ->pluck('status', 'medicine_name')
                ->toArray();
        }

        $stockSummary = null;
        try {
            $store = $this->resolveStore();
            $nearExpiryDate = Carbon::now()->addDays($store->near_expiry_days)->toDateString();

            $base = StockBatch::query()
                ->where('pharmacy_store_id', $store->id);

            $agg = (clone $base)
                ->where('quantity_on_hand', '>', 0)
                ->selectRaw('COUNT(DISTINCT medicine_id) as medicines_in_stock')
                ->selectRaw('SUM(quantity_on_hand) as units_in_stock')
                ->selectRaw('SUM(COALESCE(purchase_price, 0) * quantity_on_hand) as stock_value')
                ->first();

            $lowStockMedicines = (clone $base)
                ->select('medicine_id')
                ->groupBy('medicine_id')
                ->havingRaw('SUM(quantity_on_hand) > 0 AND SUM(quantity_on_hand) <= ?', [$store->low_stock_threshold])
                ->count();

            $nearExpiryMedicines = (clone $base)
                ->select('medicine_id')
                ->groupBy('medicine_id')
                ->havingRaw('SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= ? THEN quantity_on_hand ELSE 0 END) > 0', [$nearExpiryDate])
                ->count();

            $stockSummary = [
                'store_name' => $store->name,
                'medicines_in_stock' => (int) ($agg?->medicines_in_stock ?? 0),
                'units_in_stock' => (int) ($agg?->units_in_stock ?? 0),
                'stock_value' => (float) ($agg?->stock_value ?? 0),
                'low_stock_medicines' => (int) $lowStockMedicines,
                'near_expiry_medicines' => (int) $nearExpiryMedicines,
            ];
        } catch (\Throwable $e) {
            $stockSummary = null;
        }

        return view('pharmacist.dashboard', compact('consultations', 'selectedConsultation', 'fulfillmentStatuses', 'stockSummary'));
    }

    public function fulfill(Request $request)
    {
        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'medicine_name' => 'required|string',
            'status' => 'required|in:pending,given,not_given',
        ]);

        $consultation = Consultation::findOrFail($request->consultation_id);
        $doctor = $consultation->doctor;
        $patient = $consultation->patient;
        $user = auth()->user();
        if (
            !$user
            || (!$user->isSuperAdmin() && (
            empty($user->hospital_admin_id)
            || (
                (!$doctor || empty($doctor->hospital_admin_id) || $doctor->hospital_admin_id !== $user->hospital_admin_id)
                && (!$patient || empty($patient->hospital_admin_id) || $patient->hospital_admin_id !== $user->hospital_admin_id)
            )
            || (!empty($consultation->pharmacist_id) && (int) $consultation->pharmacist_id !== (int) $user->id)
            ))
        ) {
            abort(403, 'Unauthorized.');
        }

        $fulfillment = PrescriptionFulfillment::updateOrCreate(
            [
                'consultation_id' => $request->consultation_id,
                'medicine_name' => $request->medicine_name,
            ],
            [
                'status' => $request->status,
                'pharmacist_id' => auth()->id(),
            ]
        );

        if ($consultation->doctor) {
            $consultation->doctor->notify(new MedicineFulfilled($fulfillment->consultation));
        }

        return back()->with('success', 'Medicine status updated.');
    }
}
