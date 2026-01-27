<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\LabReport;
use App\Models\User;
use App\Notifications\LabReportUploaded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LabAssistantController extends Controller
{
    private function resolveHospitalId(?User $user): ?int
    {
        if (!$user) {
            return null;
        }
        if ($user->isHospitalAdmin()) {
            return $user->id;
        }
        return $user->hospital_admin_id ?: null;
    }

    private function canAccessConsultationLab(User $user, Consultation $consultation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $hospitalId = $this->resolveHospitalId($user);
        if (!$hospitalId) {
            return false;
        }

        if ($consultation->doctor_id && (int) $consultation->doctor_id === (int) $hospitalId) {
            return true;
        }

        if ($consultation->doctor && $consultation->doctor->hospital_admin_id && (int) $consultation->doctor->hospital_admin_id === (int) $hospitalId) {
            return true;
        }

        if ($consultation->patient && $consultation->patient->hospital_admin_id && (int) $consultation->patient->hospital_admin_id === (int) $hospitalId) {
            return true;
        }

        return false;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Consultation::orderBy('created_at', 'desc');
        if ($user && !$user->isSuperAdmin()) {
            if (Schema::hasColumn('users', 'hospital_admin_id')) {
                $hospitalId = $this->resolveHospitalId($user);
                if ($hospitalId) {
                    $doctorIds = User::where('hospital_admin_id', $hospitalId)
                        ->where('role', User::ROLE_DOCTOR)
                        ->pluck('id');
                    $query->where(function ($w) use ($hospitalId, $doctorIds) {
                        $w->where('doctor_id', $hospitalId);
                        if ($doctorIds->isNotEmpty()) {
                            $w->orWhereIn('doctor_id', $doctorIds);
                        }
                        $w->orWhere(function ($q) use ($hospitalId) {
                            $q->whereNotNull('patient_id')
                                ->whereHas('patient', function ($p) use ($hospitalId) {
                                    $p->where('hospital_admin_id', $hospitalId);
                                });
                        });
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        $consultations = $query->get();
        
        $selectedConsultation = null;
        if ($request->has('id')) {
            $selectedConsultation = $consultations->firstWhere('id', $request->id);
        }

        return view('lab.dashboard', compact('consultations', 'selectedConsultation'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'notes' => 'nullable|string',
        ]);

        $consultation = Consultation::findOrFail($request->consultation_id);
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if (!$this->canAccessConsultationLab($user, $consultation)) {
            abort(403, 'Unauthorized.');
        }

        $file = $request->file('report_file');

        if (!$file) {
            return back()->with('error', 'No file was uploaded.');
        }

        if (!$file->isValid()) {
            $errorMessage = 'File upload failed.';
            switch ($file->getError()) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'The file is too large. Server limit is ' . ini_get('upload_max_filesize') . '.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'The file was only partially uploaded.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = 'Missing a temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMessage = 'A PHP extension stopped the file upload.';
                    break;
                default:
                    $errorMessage = 'Unknown upload error.';
            }
            return back()->with('error', $errorMessage);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'report_file' => 'mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $path = $request->file('report_file')->store('lab_reports', 'public');

        $report = LabReport::create([
            'consultation_id' => $request->consultation_id,
            'file_path' => $path,
            'notes' => $request->notes,
            'uploaded_by' => auth()->id(),
        ]);

        // Notify Doctors
        $hospitalId = $this->resolveHospitalId($user);
        $doctorsQuery = User::where('role', User::ROLE_DOCTOR);
        if ($hospitalId) {
            $doctorsQuery->where('hospital_admin_id', $hospitalId);
        }
        $doctors = $doctorsQuery->get();
        foreach ($doctors as $doctor) {
            $doctor->notify(new LabReportUploaded($report));
        }

        return back()->with('success', 'Report uploaded successfully.');
    }

    public function destroy($id)
    {
        $report = LabReport::findOrFail($id);
        $consultation = $report->consultation;
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }
        if (!$consultation || !$this->canAccessConsultationLab($user, $consultation)) {
            abort(403, 'Unauthorized.');
        }
        $report->delete();
        return back()->with('success', 'Report deleted.');
    }

    public function viewReport($id)
    {
        $report = LabReport::findOrFail($id);
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isSuperAdmin()) {
            $path = storage_path('app/public/' . $report->file_path);
            if (!file_exists($path)) {
                abort(404, 'File not found.');
            }
            return response()->file($path);
        }
        
        if (auth()->user()->isPharmacist()) {
            abort(403, 'Unauthorized.');
        }

        $consultation = $report->consultation;
        if (!$consultation || !$this->canAccessConsultationLab($user, $consultation)) {
            abort(403, 'Unauthorized.');
        }

        $path = storage_path('app/public/' . $report->file_path);

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path);
    }
}
