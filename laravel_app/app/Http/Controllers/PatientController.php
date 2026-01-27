<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || (!$user->isPatient() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $consultations = Consultation::where('patient_id', $user->id)
            ->orWhere(function ($query) use ($user) {
                $query->whereNull('patient_id')
                    ->where('patient_name', $user->name);
            })
            ->with('labReports')
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedConsultation = null;
        if ($request->has('id')) {
            $selectedConsultation = $consultations->find($request->id);
        }

        return view('patient.dashboard', compact('consultations', 'selectedConsultation'));
    }

    public function connectVisits(Request $request)
    {
        $user = auth()->user();

        if (!$user || (!$user->isPatient() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isSuperAdmin() && !$user->isPatient()) {
            Log::warning('patient.connect_visits.blocked', [
                'reason' => 'super_admin',
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Unauthorized.');
        }

        if ($user->visit_connected) {
            return redirect()->back()->with('error', 'Visits have already been connected. You can only do this once.');
        }

        $request->validate([
            'mrn' => 'required|string'
        ]);

        $targetMrn = trim($request->input('mrn'));

        $existingPatient = User::where('role', User::ROLE_PATIENT)
            ->where('mrn', $targetMrn)
            ->first();

        $consultationsExist = Consultation::where('mrn', $targetMrn)->exists();

        if (!$existingPatient && !$consultationsExist) {
            return redirect()->back()->with('error', "No visits found for MRN: $targetMrn.");
        }

        if ($existingPatient && $existingPatient->id !== $user->id) {
            if ($user->isSuperAdmin()) {
                Log::warning('patient.connect_visits.blocked', [
                    'reason' => 'super_admin_merge_attempt',
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);
                abort(403, 'Unauthorized.');
            }

            $signupMrn = $user->mrn;
            $mergedCount = 0;

            DB::transaction(function () use ($existingPatient, $user, $targetMrn, $signupMrn, &$mergedCount) {
                $movedFromCurrent = Consultation::where('patient_id', $user->id)
                    ->update(['patient_id' => $existingPatient->id]);

                $linkedByMrn = Consultation::where('mrn', $targetMrn)
                    ->where(function ($q) use ($existingPatient) {
                        $q->whereNull('patient_id')
                          ->orWhere('patient_id', '!=', $existingPatient->id);
                    })
                    ->update(['patient_id' => $existingPatient->id]);

                $mergedCount = $movedFromCurrent + $linkedByMrn;

                if ($signupMrn && $signupMrn !== $targetMrn) {
                    Consultation::where('mrn', $signupMrn)
                        ->where('patient_id', $existingPatient->id)
                        ->update(['mrn' => $targetMrn]);
                }

                $existingPatient->name = $user->name;
                $existingPatient->email = $user->email;
                $existingPatient->password = $user->password;
                $existingPatient->status = User::STATUS_ACTIVE;
                $existingPatient->visit_connected = true;

                $user->delete();

                $existingPatient->save();
            });

            Auth::login($existingPatient);

            return redirect()->route('patient.dashboard')
                ->with('status', "Account merged successfully. Connected $mergedCount visits using MRN: $targetMrn.");
        }

        $updatedCount = Consultation::where('mrn', $targetMrn)
            ->where(function ($q) use ($user) {
                $q->whereNull('patient_id')
                  ->orWhere('patient_id', '!=', $user->id);
            })
            ->update(['patient_id' => $user->id]);

        $user->visit_connected = true;
        $user->save();

        return redirect()->back()->with('status', "Successfully connected $updatedCount visits from MRN: $targetMrn.");
    }
}
