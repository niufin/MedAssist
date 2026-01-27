<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Consultation;
use App\Models\AiPrescriptionCache;
use App\Models\Medicine;
use App\Models\User;
use App\Models\LabReport;
use App\Notifications\PrescriptionGenerated;
use App\Notifications\LabInvestigationRequested;
use App\Services\Pharmacy\PrescriptionDispenseSyncService;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; // <--- IMPORT THIS
use Illuminate\Support\Facades\Schema;

class DoctorController extends Controller
{
    // ... [index, destroy, destroyAll, newPatient methods REMAIN SAME] ...

    private function doctorHasPatientAccess(User $doctor, int $patientId): bool
    {
        return $doctor->assignedPatients()->where('users.id', $patientId)->exists();
    }

    private function canAccessConsultation(User $user, Consultation $c): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isHospitalAdmin()) {
            if ($c->doctor_id && $c->doctor_id === $user->id) {
                return true;
            }
            if ($c->patient_id && User::where('id', $c->patient_id)->where('hospital_admin_id', $user->id)->exists()) {
                return true;
            }
            if ($c->doctor_id && User::where('id', $c->doctor_id)->where('hospital_admin_id', $user->id)->exists()) {
                return true;
            }
            return false;
        }

        if ($user->isDoctor()) {
            if ($c->doctor_id && $c->doctor_id === $user->id) {
                return true;
            }
            if ($c->patient_id) {
                return $this->doctorHasPatientAccess($user, (int) $c->patient_id);
            }
            return false;
        }

        if (method_exists($user, 'isPatient') && $user->isPatient()) {
            return $c->patient_id && $c->patient_id === $user->id;
        }

        return false;
    }

    private function authorizeConsultationAccess(Consultation $c): void
    {
        $user = auth()->user();
        if (!$user || !$this->canAccessConsultation($user, $c)) {
            abort(403, 'Unauthorized.');
        }
    }

    public function patients(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isDoctor() && !$user->isHospitalAdmin() && !$user->isAdmin())) {
            abort(403);
        }

        $query = User::where('role', User::ROLE_PATIENT);

        if ($user->isHospitalAdmin()) {
            $query->where('hospital_admin_id', $user->id);
        }
        // If doctor, they can typically see all patients in the system or their hospital
        // Assuming doctors can see all patients for now as per "My Patients" usually implies access to all treated.
        // Or if scoped to hospital:
        if ($user->isDoctor() && $user->hospital_admin_id) {
             $query->where('hospital_admin_id', $user->hospital_admin_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mrn', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate(20);

        return view('doctor.patients.index', compact('patients'));
    }

    public function showPatient($id)
    {
        $patient = User::findOrFail($id);
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }
        if ($user->isDoctor()) {
            $allowed = false;
            if ($patient->hospital_admin_id && $user->hospital_admin_id && $patient->hospital_admin_id === $user->hospital_admin_id) {
                $allowed = true;
            }
            if (!$allowed) {
                $allowed = $this->doctorHasPatientAccess($user, (int) $patient->id);
            }
            if (!$allowed) {
                abort(403);
            }
        }
        if ($user->isHospitalAdmin()) {
            if (!$patient->hospital_admin_id || $patient->hospital_admin_id !== $user->id) {
                abort(403);
            }
        }
        
        $consultationsQuery = Consultation::where('patient_id', $patient->id)->latest();
        if ($user->isDoctor()) {
            if (!$this->doctorHasPatientAccess($user, (int) $patient->id)) {
                $consultationsQuery->where('doctor_id', $user->id);
            }
        }
        $consultations = $consultationsQuery->get();

        return view('doctor.patients.show', compact('patient', 'consultations'));
    }

    public function newConsultationForPatient($id)
    {
        $patient = User::findOrFail($id);
        $age = $patient->age;
        $gender = $patient->gender;
        if ($this->isUnknownValue($age) || $this->isUnknownValue($gender)) {
            $last = Consultation::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->first();
            if ($last) {
                if ($this->isUnknownValue($age) && !$this->isUnknownValue($last->patient_age)) {
                    $age = $last->patient_age;
                }
                if ($this->isUnknownValue($gender) && !$this->isUnknownValue($last->patient_gender)) {
                    $gender = $last->patient_gender;
                }
            }

            $patientDirty = false;
            if ($this->isUnknownValue($patient->age) && !$this->isUnknownValue($age)) {
                $patient->age = $age;
                $patientDirty = true;
            }
            if ($this->isUnknownValue($patient->gender) && !$this->isUnknownValue($gender)) {
                $patient->gender = $gender;
                $patientDirty = true;
            }
            if ($patientDirty) {
                $patient->save();
            }
        }
        
        $c = new Consultation();
        $c->patient_id = $patient->id;
        $c->patient_name = $patient->name;
        $c->patient_age = $this->isUnknownValue($age) ? 'Unknown' : $age;
        $c->patient_gender = $this->isUnknownValue($gender) ? 'Unknown' : $gender;
        $c->mrn = $patient->mrn;
        
        $c->status = 'consulting'; // Skip registration
        $c->symptoms = 'Pending Intake'; 
        
        if (auth()->user()->isDoctor() || auth()->user()->isHospitalAdmin()) {
            $c->doctor_id = auth()->id();
        }

        $history = [
            [
                'role' => 'assistant', 
                'content' => "👋 **New Consultation for {$patient->name}**\n\nPatient details loaded (MRN: {$patient->mrn}, Age: {$c->patient_age}, Gender: {$c->patient_gender}).\n\nPlease describe the **Symptoms** to begin diagnosis.",
                'model' => 'System'
            ]
        ];
        $c->chat_history = json_encode($history);
        $c->save();

        return redirect()->route('dashboard', ['id' => $c->id]);
    }

    // --- 1. DASHBOARD LOAD ---
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isPharmacist()) {
            return redirect()->route('pharmacist.dashboard');
        }
        if ($user && $user->isLabAssistant()) {
            return redirect()->route('lab.dashboard');
        }
        if ($user && method_exists($user, 'isPatient') && $user->isPatient()) {
            return redirect()->route('patient.dashboard');
        }

        $activeSessionId = $request->query('id');
        $search = $request->query('search');
        $dbError = null;
        try {
            $query = Consultation::latest();

            if ($user) {
                if ($user->isDoctor()) {
                    $patientIds = $user->assignedPatients()->pluck('users.id')->toArray();
                    $query->where(function ($w) use ($user, $patientIds) {
                        $w->where('doctor_id', $user->id);
                        if (!empty($patientIds)) {
                            $w->orWhereIn('patient_id', $patientIds);
                        }
                    });
                } elseif ($user->isHospitalAdmin()) {
                    $doctorIds = $user->hospitalUsers()
                        ->where('role', User::ROLE_DOCTOR)
                        ->pluck('id');
                    $query->where(function ($w) use ($user, $doctorIds) {
                        $w->where('doctor_id', $user->id);
                        if ($doctorIds->isNotEmpty()) {
                            $w->orWhereIn('doctor_id', $doctorIds);
                        }
                        $w->orWhere(function ($q) use ($user) {
                            $q->whereNull('doctor_id')
                                ->whereNotNull('patient_id')
                                ->whereHas('patient', function ($p) use ($user) {
                                    $p->where('hospital_admin_id', $user->id);
                                });
                        });
                    });
                }
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                      ->orWhere('symptoms', 'like', "%{$search}%")
                      ->orWhere('patient_gender', 'like', "%{$search}%");
                });
            }
            $history = $query->with('patient')->get();
            $activeSession = null;
            $sidebarSources = [];
            if ($activeSessionId) {
                $activeSession = Consultation::find($activeSessionId);
                if ($activeSession && $activeSession->chat_history) {
                    $agg = [];
                    $messages = json_decode($activeSession->chat_history, true) ?? [];
                    foreach ($messages as $m) {
                        if (isset($m['sources']) && is_array($m['sources'])) {
                            foreach ($m['sources'] as $src) {
                                $key = $src['source'] ?? '';
                                if (!$key) continue;
                                if (!isset($agg[$key])) $agg[$key] = $src;
                            }
                        }
                    }
                    $sidebarSources = array_values($agg);
                }
            }
        } catch (\Throwable $e) {
            $history = collect();
            $activeSession = null;
            $dbError = 'Database connection error. Please check configuration.';
            $sidebarSources = [];
        }

        $summary = null;
        if ($user && $user->isHospitalAdmin()) {
            try {
                if (Schema::hasColumn('users', 'hospital_admin_id')) {
                    $doctorIds = User::where('hospital_admin_id', $user->id)
                        ->where('role', User::ROLE_DOCTOR)
                        ->pluck('id');
                    $summary = [
                        'doctors' => $doctorIds->count(),
                        'patients' => User::where('hospital_admin_id', $user->id)->where('role', User::ROLE_PATIENT)->count(),
                        'pharmacists' => User::where('hospital_admin_id', $user->id)->where('role', User::ROLE_PHARMACIST)->count(),
                        'lab_assistants' => User::where('hospital_admin_id', $user->id)->where('role', User::ROLE_LAB_ASSISTANT)->count(),
                        'consultations' => $doctorIds->isNotEmpty() ? Consultation::whereIn('doctor_id', $doctorIds)->count() : 0,
                    ];
                } else {
                    $summary = null;
                }
            } catch (\Throwable $e) {
                $summary = null;
            }
        }

        return view('doctor_dashboard', [
            'history' => $history,
            'session' => $activeSession,
            'search' => $search,
            'db_error' => $dbError,
            'sidebar_sources' => $sidebarSources,
            'summary' => $summary,
        ]);
    }

    public function destroy($id)
    {
        try {
            $consultation = Consultation::findOrFail($id);
            $consultation->delete();
            return redirect()->route('dashboard')->with('status', 'Patient record deleted.');
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Database error while deleting record.');
        }
    }

    public function destroyAll()
    {
        try {
            // Clean up Prescription Files
            $consultations = Consultation::whereNotNull('prescription_path')->get();
            foreach ($consultations as $c) {
                if ($c->prescription_path && file_exists($c->prescription_path)) {
                    @unlink($c->prescription_path);
                }
            }

            // Clean up Lab Report Files
            $reports = LabReport::all();
            foreach ($reports as $r) {
                $path = storage_path('app/public/' . $r->file_path);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            // Delete all records (DB Cascade will remove LabReports and Fulfillments)
            Consultation::query()->delete();
            
            return redirect()->route('dashboard')->with('status', 'All records cleared.');
        } catch (\Throwable $e) {
            Log::error("Clear All Failed: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Database error while clearing records: ' . $e->getMessage());
        }
    }

    public function newPatient()
    {
        if (auth()->user()->isPharmacist() || auth()->user()->isLabAssistant()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $c = new Consultation();
            $c->mrn = $this->generateUniqueMrn();
            $c->status = 'asking_name';
            $c->symptoms = 'Pending Intake'; 
            $user = auth()->user();
            if ($user && ($user->isDoctor() || $user->isHospitalAdmin())) {
                $c->doctor_id = $user->id;
            }
            $history = [
                [
                    'role' => 'assistant', 
                    'content' => "👋 **New Patient Intake**\n\nLet's start. **Question 1/3:**\nWhat is the patient's **Name**?\n*(Or type 'Sarah, 25 female, fever' to skip)*",
                    'model' => 'System'
                ]
            ];
            $c->chat_history = json_encode($history);
            $c->save();
            return redirect()->route('dashboard', ['id' => $c->id]);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Database error while starting new patient.');
        }
    }

    // --- NEW: GENERATE PRESCRIPTION PDF ---
    public function generatePrescription($id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);

        if (!$c->ai_analysis && empty($c->prescription_data)) {
            return redirect()->back()->with('error', 'No prescription data available yet. Please generate or enter the prescription first.');
        }

        try {
            $pdfDoctor = $c->doctor ?: auth()->user();
            $pdf = Pdf::loadView('prescription_pdf', ['c' => $c, 'doctor' => $pdfDoctor])->setPaper('A4', 'portrait');
            $filenameBase = Str::slug($c->patient_name) ?: 'patient';
            $storageDir = storage_path('app/public/prescriptions');
            if (!file_exists($storageDir)) mkdir($storageDir, 0777, true);
            $fullPath = $storageDir . DIRECTORY_SEPARATOR . 'Prescription_' . $filenameBase . '_' . $c->id . '.pdf';
            $written = @file_put_contents($fullPath, $pdf->output());
            if ($written !== false) {
                try {
                    $c->prescription_path = $fullPath;
                    $c->save();
                } catch (\Throwable $saveErr) {
                    // ignore DB save error; still allow download
                }
                return response()->download($fullPath);
            }
            return $pdf->download('Prescription_' . $filenameBase . '.pdf');
        } catch (\Throwable $e) {
            return response()
                ->view('prescription_pdf', ['c' => $c, 'doctor' => ($c->doctor ?: auth()->user())])
                ->withHeaders([
                    'X-Prescription-Error' => 'PDF generation failed: ' . $e->getMessage(),
                ]);
        }
    }

    public function previewPrescription($id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);
        return view('prescription_preview', ['c' => $c]);
    }

    public function previewPrescriptionRaw($id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);
        return view('prescription_pdf', ['c' => $c, 'doctor' => ($c->doctor ?: auth()->user())]);
    }

    public function downloadPrescription($id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);

        if (!$c->prescription_path || !file_exists($c->prescription_path)) {
            return redirect()->back()->with('error', 'No saved prescription found.');
        }
        return response()->download($c->prescription_path);
    }

    public function attachPatient(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user || !($user->isDoctor() || $user->isAdmin() || $user->isSuperAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'identifier' => 'required|string',
        ]);

        try {
            $consultation = Consultation::findOrFail($id);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard', ['id' => $id])->with('error', 'Consultation not found.');
        }

        $identifier = trim($request->input('identifier'));

        $patientQuery = User::where('role', User::ROLE_PATIENT);

        if (str_contains($identifier, '@')) {
            $patientQuery->where('email', $identifier);
        } else {
            $patientQuery->where('mrn', $identifier);
        }

        $patient = $patientQuery->first();

        if (!$patient) {
            return redirect()->route('dashboard', ['id' => $id])->with('error', 'No patient found for this identifier.');
        }

        $consultation->patient_id = $patient->id;
        if (!$consultation->mrn && !empty($patient->mrn)) {
            $consultation->mrn = $patient->mrn;
        }

        if (!$consultation->patient_name) {
            $consultation->patient_name = $patient->name;
        }

        if ($this->isUnknownValue($consultation->patient_age) && !$this->isUnknownValue($patient->age)) {
            $consultation->patient_age = $patient->age;
        }
        if ($this->isUnknownValue($consultation->patient_gender) && !$this->isUnknownValue($patient->gender)) {
            $consultation->patient_gender = $patient->gender;
        }

        $patientDirty = false;
        if ($this->isUnknownValue($patient->age) && !$this->isUnknownValue($consultation->patient_age)) {
            $patient->age = $consultation->patient_age;
            $patientDirty = true;
        }
        if ($this->isUnknownValue($patient->gender) && !$this->isUnknownValue($consultation->patient_gender)) {
            $patient->gender = $consultation->patient_gender;
            $patientDirty = true;
        }
        if ($patientDirty) {
            $patient->save();
        }

        $consultation->save();

        return redirect()->route('dashboard', ['id' => $id])->with('status', 'Consultation linked to patient.');
    }

    public function backfillPatients()
    {
        $actor = auth()->user();
        if (!$actor || (!$actor->isDoctor() && !$actor->isHospitalAdmin() && !$actor->isAdmin() && !$actor->isSuperAdmin())) {
            abort(403, 'Unauthorized.');
        }
        
        $consultationsQuery = Consultation::whereNull('patient_id');
        $hospitalId = $this->resolveHospitalAdminIdForUser($actor);
        if ($actor->isDoctor()) {
            $consultationsQuery->where('doctor_id', $actor->id);
        } elseif ($actor->isHospitalAdmin()) {
            $doctorIds = $actor->hospitalUsers()
                ->where('role', User::ROLE_DOCTOR)
                ->pluck('id');
            $consultationsQuery->where(function ($q) use ($actor, $doctorIds) {
                $q->where('doctor_id', $actor->id);
                if ($doctorIds->isNotEmpty()) {
                    $q->orWhereIn('doctor_id', $doctorIds);
                }
            });
        }

        $consultations = $consultationsQuery->get();
        $count = 0;
        
        foreach ($consultations as $c) {
            if ($c->patient_name) {
                $userQuery = User::where('name', $c->patient_name)
                    ->where('role', User::ROLE_PATIENT);
                if ($hospitalId) {
                    $userQuery->where('hospital_admin_id', $hospitalId);
                }
                $user = $userQuery->first();
                if ($user) {
                    $c->patient_id = $user->id;
                    $c->save();
                    $count++;
                }
            }
        }
        
        return redirect()->back()->with('status', "Backfilled $count consultations with patient accounts.");
    }

    // --- CHAT LOGIC ---
    public function chat(Request $request)
    {
        $request->validate(['consultation_id' => 'required']);
        $userMessage = $request->message;
        $mode = $request->input('mode', 'chat');

        if ($mode === 'final' && empty($userMessage)) {
            $userMessage = "Please generate the Final Diagnosis Report now.";
        }
        if (empty($userMessage)) return redirect()->back();

        try {
            $c = Consultation::findOrFail($request->consultation_id);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Database error while loading consultation.');
        }
        $chatHistory = json_decode($c->chat_history, true) ?? [];
        $chatHistory[] = ['role' => 'user', 'content' => $userMessage];

        // Auto Detect
        if ($c->status === 'asking_name') {
            $detected = $this->parsePatientDetails($userMessage);
            
            // Try to link patient by Email or MRN
            $linkedUser = null;
            if (!empty($detected['email'])) {
                $linkedUser = User::where('email', $detected['email'])->where('role', User::ROLE_PATIENT)->first();
            } elseif (!empty($detected['mrn'])) {
                $linkedUser = User::where('mrn', $detected['mrn'])->where('role', User::ROLE_PATIENT)->first();
            }

            if ($linkedUser) {
                $c->patient_id = $linkedUser->id;
                if (!$c->mrn && !empty($linkedUser->mrn)) {
                    $c->mrn = $linkedUser->mrn;
                }
                // Prefer registered name if linked
                $detected['name'] = $linkedUser->name;
                if (!$detected['age'] && !$this->isUnknownValue($linkedUser->age ?? null)) {
                    $detected['age'] = $linkedUser->age;
                }
                if (!$detected['gender'] && !$this->isUnknownValue($linkedUser->gender ?? null)) {
                    $detected['gender'] = $linkedUser->gender;
                }
            }

            // If we have full info (Name, Age, Gender)
            if ($detected['name'] && $detected['age'] && $detected['gender']) {
                $c->patient_name = $detected['name'];
                $c->patient_age = $detected['age'];
                $c->patient_gender = $detected['gender'];
                $c->symptoms = $detected['symptoms']; 
                $c->status = 'consulting'; 
                $c->save();
                $this->ensurePatientAccount($c);

                $systemMsg = "✅ **Auto-Detected:** {$c->patient_name} | {$c->patient_age} | {$c->patient_gender}";
                if ($linkedUser) {
                    $systemMsg .= "\n🔗 **Linked to Patient:** {$linkedUser->name} (MRN: {$linkedUser->mrn})";
                }
                $systemMsg .= "\n🚀 Jumping to Diagnosis...";
                
                $chatHistory[] = ['role' => 'assistant', 'content' => $systemMsg, 'model' => 'System'];
                return $this->runAiDiagnosis($c, $chatHistory, $c->symptoms, $mode);
            }
            
            // Partial Detection: We have a linked user but missing Age/Gender
            if ($linkedUser) {
                $c->patient_name = $linkedUser->name;
                $msg = "✅ **Linked to Patient:** {$linkedUser->name} (MRN: {$linkedUser->mrn}).";

                if (!$detected['age']) {
                    $c->status = 'asking_age';
                    $c->save();
                    $msg .= "\nHowever, **Age** is missing. How old is the patient?";
                    $this->appendAndSave($c, $chatHistory, $msg, "System", []);
                    return redirect()->back();
                }
                
                if (!$detected['gender']) {
                    $c->patient_age = $detected['age'];
                    $c->status = 'asking_gender';
                    $c->save();
                    $msg .= "\nAge: {$detected['age']}. What is the **Gender**?";
                    $this->appendAndSave($c, $chatHistory, $msg, "System", []);
                    return redirect()->back();
                }
            }
        }

        // Standard Flow (Fallback if Auto-Detect didn't fully transition or link)
        if ($c->status === 'asking_name') {
            try {
                $c->patient_name = $userMessage;
                $c->status = 'asking_age';
                $c->save();
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Database error while saving name.');
            }
            $this->appendAndSave($c, $chatHistory, "✅ Name recorded. How **Old** is the patient?", "System", []);
            return redirect()->back();
        }
        if ($c->status === 'asking_age') {
            try {
                $c->patient_age = $userMessage;
                $c->status = 'asking_gender';
                $c->save();
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Database error while saving age.');
            }
            $this->appendAndSave($c, $chatHistory, "✅ Age recorded. What is the **Gender**?", "System", []);
            return redirect()->back();
        }
        if ($c->status === 'asking_gender') {
            try {
                $c->patient_gender = $userMessage;
                $c->status = 'consulting';
                $c->save();
                $this->ensurePatientAccount($c);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Database error while saving gender.');
            }
            $this->appendAndSave($c, $chatHistory, "✅ Registration Complete. Describe the **Symptoms**.", "System", []);
            return redirect()->back();
        }

        if ($c->status === 'consulting' || $c->status === 'finished') {
            if ($c->symptoms === 'Pending Intake') {
                try {
                    $c->symptoms = $userMessage;
                    $c->save();
                } catch (\Throwable $e) {
                    return redirect()->back()->with('error', 'Database error while saving symptoms.');
                }
            }
            $aiInput = $userMessage;
            $expectPrescription = false;
            if ($mode === 'final') {
                $aiInput = $this->buildFinalConsultPrompt($c, $userMessage, $chatHistory);
                $expectPrescription = true;
            }
            return $this->runAiDiagnosis($c, $chatHistory, $aiInput, $mode, $expectPrescription);
        }

        return redirect()->back();
    }

    public function submitIntake(Request $request)
    {
        $data = $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'patient_name' => 'required|string|max:255',
            'patient_age' => 'required|string|max:50',
            'patient_gender' => 'required|string|max:50',
            'symptoms' => 'required|string',
        ]);

        try {
            $c = Consultation::findOrFail($data['consultation_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Database error while saving intake.');
        }

        $c->patient_name = $data['patient_name'];
        $c->patient_age = $data['patient_age'];
        $c->patient_gender = $data['patient_gender'];
        $c->symptoms = $data['symptoms'];
        $c->status = 'consulting';
        $this->ensurePatientAccount($c);

        $chatHistory = json_decode($c->chat_history, true) ?? [];
        $summary = "✅ Intake form submitted.\nName: {$c->patient_name}\nAge: {$c->patient_age}\nGender: {$c->patient_gender}\nSymptoms: {$c->symptoms}";
        $chatHistory[] = ['role' => 'assistant', 'content' => $summary, 'model' => 'System'];

        try {
            $c->chat_history = json_encode($chatHistory);
            $c->save();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Database error while saving intake.');
        }

        return $this->runAiDiagnosis($c, $chatHistory, $c->symptoms, 'chat');
    }

    // --- UPLOAD REPORT ---
    public function uploadReport(Request $request)
    {
        $request->validate([
            'reports' => 'required',
            'reports.*' => 'file|mimes:pdf,jpg,jpeg,png,bmp,tiff|max:10240',
            'consultation_id' => 'required'
        ]);

        try {
            $c = Consultation::findOrFail($request->consultation_id);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Database error while loading consultation.');
        }
        $destinationPath = storage_path('app/public/reports');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $chatHistory = json_decode($c->chat_history, true) ?? [];
        $files = $request->file('reports', []);
        $uploadedCount = 0;

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $filename);
            $fullPath = $destinationPath . '/' . $filename;

            $extractedText = "Error reading text.";
            try {
                $aiUrl = config('services.ai_service.url');
                $response = Http::timeout(60)->post($aiUrl . '/read-report', ['file_path' => $fullPath]);
                $data = $response->json();
                if (is_array($data) && !empty($data['text'])) {
                    $extractedText = $data['text'];
                }
            } catch (\Exception $e) {
                Log::error('read-report failed: ' . $e->getMessage());
            }

            $userMsg = "📄 **UPLOADED REPORT:** " . $file->getClientOriginalName() . "\n" .
                       "<details><summary class='cursor-pointer text-blue-500 text-xs font-bold mt-2'>Click to view extracted text content</summary>\n" .
                       "<div class='mt-2 p-2 bg-gray-100 rounded text-xs font-mono text-gray-600 max-h-40 overflow-y-auto'>\n" .
                       "[SYSTEM: LAB DATA START]\n" . $extractedText . "\n[SYSTEM: LAB DATA END]\n" .
                       "</div></details>";

            $chatHistory[] = ['role' => 'user', 'content' => $userMsg];

            LabReport::create([
                'consultation_id' => $c->id,
                'file_path' => 'reports/' . $filename,
                'uploaded_by' => auth()->id(),
                'notes' => 'Uploaded by Doctor',
            ]);

            $uploadedCount++;
        }

        $c->chat_history = json_encode($chatHistory);
        $c->save();

        if ($uploadedCount === 0) {
            return redirect()->back()->with('error', 'No valid files were uploaded.');
        }

        return redirect()->back()->with('success', $uploadedCount . ' report(s) uploaded successfully. Click "Analyze Lab Reports" to process.');
    }

    // --- UPDATE PRESCRIPTION FROM CHAT ---
    public function updatePrescriptionFromChat(Request $request)
    {
        $request->validate(['consultation_id' => 'required']);
        $c = Consultation::findOrFail($request->consultation_id);
        $this->authorizeConsultationAccess($c);
        $this->ensureDoctorPatientAccess($c);
        
        $chatHistory = json_decode($c->chat_history, true) ?? [];
        $userMsg = $request->message;
        
        // Add user message to history if provided
        if (!empty($userMsg)) {
            $chatHistory[] = ['role' => 'user', 'content' => $userMsg];
        }
        
        // --- ROBUST FALLBACK: Fetch texts from all LabReports directly ---
        $labTexts = "";
        $reports = LabReport::where('consultation_id', $c->id)->get();
        if ($reports->count() > 0) {
            $aiUrl = config('services.ai_service.url');
            foreach ($reports as $report) {
                try {
                    $response = Http::timeout(30)->post($aiUrl . '/read-report', [
                        'file_path' => storage_path('app/public/' . $report->file_path)
                    ]);
                    $data = $response->json();
                    $text = $data['text'] ?? "";
                    if (strlen($text) > 10) {
                        $labTexts .= "\n\n--- REPORT: " . basename($report->file_path) . " ---\n" . $text;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to re-read report: " . $e->getMessage());
                }
            }
        }
        
        $schema = '{
    "diagnosis": "Updated Diagnosis",
    "clinical_notes": "C/O: ...\nO/E: ...",
    "investigations": "CBC, CRP, CXR",
    "medicines": [
        {"name": "Tab. Paracetamol", "dosage": "500mg", "frequency": "1-0-1", "duration": "5 days", "instruction": "After food"}
    ],
    "advice": "Updated advice..."
}';

        $currentRx = $c->prescription_data;
        if (is_array($currentRx) || is_object($currentRx)) {
            $currentRx = json_encode($currentRx, JSON_PRETTY_PRINT);
        }
        $currentRx = $currentRx ?: "None";
        $summaryPrompt = "The doctor wants to UPDATE the prescription.\n";
        if (!empty($userMsg)) {
            $summaryPrompt .= "Doctor's Note: \"$userMsg\".\n";
        }
        if (!empty($labTexts)) {
            $summaryPrompt .= "New Lab Reports are available (see below).\n";
        }
        
        $summaryPrompt .= "Based on this new information, previous history, and current prescription, please generate the UPDATED prescription structure.\n\n" .
            "CURRENT PRESCRIPTION (Do NOT remove chronic meds unless contraindicated):\n" . $currentRx . "\n\n" .
            "REQUIRED FORMAT:\n" .
            "[PRESCRIPTION_START]\n" .
            $schema . "\n" .
            "[PRESCRIPTION_END]\n\n" .
            "INSTRUCTIONS:\n" .
            "1. Merge the CURRENT PRESCRIPTION with new findings.\n" .
            "2. Keep existing chronic medications (e.g. for Diabetes, BP) if they are still relevant.\n" .
            "3. Consider the consultation's primary symptoms AND all secondary symptoms in the full chat when updating medicines.\n" .
            "4. Add new medications based on the new symptoms/feedback/reports.\n" .
            "4. Clinical notes must include BOTH sections with labels: 'C/O:' and 'O/E:'.\n" .
            "5. Investigations must be comma-separated short forms as doctors write (e.g., 'CBC, CRP, CXR').\n" .
            "6. Medicine 'name' must be SHORT dosage-form prefix + generic composition (e.g., 'Tab. Paracetamol', 'Syr. Cetirizine').\n" .
            "7. Do NOT use vague terms like 'Cough', 'Antihistamine', 'Painkiller' as medicine names.\n" .
            "8. Prefer medicines that are supported by the provided context/guideline documents; if not available, use standard first-line generics.\n" .
            "9. NON-CREATIVE MODE: Provide a single best, conservative plan. Do not add speculative/optional medicines.\n" .
            "10. COMPLETENESS: Ensure symptom-control medicines are not omitted when clearly indicated by the notes (fever/pain, nausea/vomiting, acidity/gas, diarrhea/ORS if present) with timing and duration.\n" .
            "11. If epigastric/upper abdominal pain, dyspepsia/GERD/heartburn/reflux, nausea/vomiting, or troublesome gas/bloating are present, include appropriate symptom relief (e.g., Tab. Pantoprazole once daily before breakfast; consider Tab. Simethicone after food) with clear duration.\n" .
            "12. Do NOT add antibiotics unless there is clear bacterial indication in the notes or labs.\n" .
            "13. The content INSIDE the tags must be valid JSON matching the schema above. Do NOT wrap the tags in quotes or braces.";
            
        if (!empty($labTexts)) {
            $summaryPrompt .= "\n\n[SYSTEM: LAB DATA START]" . $labTexts . "\n[SYSTEM: LAB DATA END]";
        }
        
        return $this->runAiDiagnosis($c, $chatHistory, $summaryPrompt, 'chat', true);
    }

    // --- ANALYZE LAB REPORTS ---
    public function analyzeReports(Request $request)
    {
        $request->validate(['consultation_id' => 'required']);
        $c = Consultation::findOrFail($request->consultation_id);
        
        $chatHistory = json_decode($c->chat_history, true) ?? [];
        
        // --- ROBUST FALLBACK: Fetch texts from all LabReports directly ---
        $labTexts = "";
        $reports = LabReport::where('consultation_id', $c->id)->get();
        if ($reports->count() > 0) {
            $aiUrl = config('services.ai_service.url');
            foreach ($reports as $report) {
                try {
                    $response = Http::timeout(30)->post($aiUrl . '/read-report', [
                        'file_path' => storage_path('app/public/' . $report->file_path)
                    ]);
                    $data = $response->json();
                    $text = $data['text'] ?? "";
                    if (strlen($text) > 10) {
                        $labTexts .= "\n\n--- REPORT: " . basename($report->file_path) . " ---\n" . $text;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to re-read report: " . $e->getMessage());
                }
            }
        }
        
        $summaryPrompt = "I have uploaded medical reports. Please analyze these findings using general medical knowledge, listing abnormal values and summarizing the key health indicators.";
        
        if (!empty($labTexts)) {
            $summaryPrompt .= "\n\n[SYSTEM: LAB DATA START]" . $labTexts . "\n[SYSTEM: LAB DATA END]";
        } else {
            $summaryPrompt .= " (Note: Check chat history for previously extracted lab data if not appended here).";
        }
        
        return $this->runAiDiagnosis($c, $chatHistory, $summaryPrompt, 'chat');
    }

    // --- UPDATE PRESCRIPTION FROM REPORTS ---
    public function updatePrescriptionFromReports(Request $request)
    {
        $request->validate(['consultation_id' => 'required']);
        $c = Consultation::findOrFail($request->consultation_id);
        $this->authorizeConsultationAccess($c);
        $this->ensureDoctorPatientAccess($c);
        
        $chatHistory = json_decode($c->chat_history, true) ?? [];
        
        // --- ROBUST FALLBACK: Fetch texts from all LabReports directly ---
        $labTexts = "";
        $reports = LabReport::where('consultation_id', $c->id)->get();
        if ($reports->count() > 0) {
            $aiUrl = config('services.ai_service.url');
            foreach ($reports as $report) {
                try {
                    $response = Http::timeout(30)->post($aiUrl . '/read-report', [
                        'file_path' => storage_path('app/public/' . $report->file_path)
                    ]);
                    $data = $response->json();
                    $text = $data['text'] ?? "";
                    if (strlen($text) > 10) {
                        $labTexts .= "\n\n--- REPORT: " . basename($report->file_path) . " ---\n" . $text;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to re-read report: " . $e->getMessage());
                }
            }
        }
        
        $schema = '{
    "diagnosis": "Updated Diagnosis",
    "clinical_notes": "C/O: ...\nO/E: ...",
    "investigations": "CBC, CRP, CXR",
    "medicines": [
        {"name": "Tab. Paracetamol", "dosage": "500mg", "frequency": "1-0-1", "duration": "5 days", "instruction": "After food"}
    ],
    "advice": "Updated advice..."
}';

        $currentRx = $c->prescription_data;
        if (is_array($currentRx) || is_object($currentRx)) {
            $currentRx = json_encode($currentRx, JSON_PRETTY_PRINT);
        }
        $currentRx = $currentRx ?: "None";
        $summaryPrompt = "Based on the analyzed lab reports (marked with [SYSTEM: LAB DATA]) and previous history, please generate the UPDATED prescription structure.\n\n" .
            "CURRENT PRESCRIPTION (Do NOT remove chronic meds unless contraindicated):\n" . $currentRx . "\n\n" .
            "REQUIRED FORMAT:\n" .
            "[PRESCRIPTION_START]\n" .
            $schema . "\n" .
            "[PRESCRIPTION_END]\n\n" .
            "INSTRUCTIONS:\n" .
            "1. Merge the CURRENT PRESCRIPTION with new findings.\n" .
            "2. Keep existing chronic medications (e.g. for Diabetes, BP) if they are still relevant.\n" .
            "3. Consider the consultation's primary symptoms AND all secondary symptoms in the full chat when updating medicines.\n" .
            "4. Add new medications based on the lab report findings.\n" .
            "4. Clinical notes must include BOTH sections with labels: 'C/O:' and 'O/E:'.\n" .
            "5. Investigations must be comma-separated short forms as doctors write (e.g., 'CBC, CRP, CXR').\n" .
            "6. Medicine 'name' must be SHORT dosage-form prefix + generic composition (e.g., 'Tab. Paracetamol', 'Syr. Cetirizine').\n" .
            "7. Do NOT use vague terms like 'Cough', 'Antihistamine', 'Painkiller' as medicine names.\n" .
            "8. Prefer medicines that are supported by the provided context/guideline documents; if not available, use standard first-line generics.\n" .
            "9. NON-CREATIVE MODE: Provide a single best, conservative plan. Do not add speculative/optional medicines.\n" .
            "10. COMPLETENESS: Ensure symptom-control medicines are not omitted when clearly indicated by the notes (fever/pain, nausea/vomiting, acidity/gas, diarrhea/ORS if present) with timing and duration.\n" .
            "11. If epigastric/upper abdominal pain, dyspepsia/GERD/heartburn/reflux, nausea/vomiting, or troublesome gas/bloating are present, include appropriate symptom relief (e.g., Tab. Pantoprazole once daily before breakfast; consider Tab. Simethicone after food) with clear duration.\n" .
            "12. Do NOT add antibiotics unless there is clear bacterial indication in the notes or labs.\n" .
            "13. The content INSIDE the tags must be valid JSON matching the schema above. Do NOT wrap the tags in quotes or braces.";
        
        if (!empty($labTexts)) {
            $summaryPrompt .= "\n\n[SYSTEM: LAB DATA START]" . $labTexts . "\n[SYSTEM: LAB DATA END]";
        }
        
        return $this->runAiDiagnosis($c, $chatHistory, $summaryPrompt, 'chat', true);
    }

    // --- HELPERS ---
    private function generateUniqueMrn()
    {
        do {
            $mrn = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (
            Consultation::where('mrn', $mrn)->exists() ||
            User::where('mrn', $mrn)->exists()
        );

        return $mrn;
    }

    private function isUnknownValue($value): bool
    {
        if ($value === null) {
            return true;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return true;
        }
        return strtolower($v) === 'unknown';
    }

    private function normalizePatientName(?string $value): string
    {
        $v = trim((string) $value);
        $v = preg_replace('/\s+/', ' ', $v);
        return strtolower(trim((string) $v));
    }

    private function normalizePatientAge(?string $value): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return '';
        }
        if (preg_match('/\d+/', $v, $m)) {
            return trim((string) $m[0]);
        }
        return '';
    }

    private function normalizePatientGender(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        if ($v === '') {
            return '';
        }
        if (in_array($v, ['m', 'male', 'man', 'boy', 'gentleman'], true)) {
            return 'male';
        }
        if (in_array($v, ['f', 'female', 'woman', 'girl', 'lady'], true)) {
            return 'female';
        }
        return $v;
    }

    private function ensurePatientAccount(Consultation $consultation)
    {
        if ($consultation->patient_id) {
            $patient = User::find($consultation->patient_id);
            if ($patient) {
                $dirty = false;
                if ($this->isUnknownValue($patient->age) && !$this->isUnknownValue($consultation->patient_age)) {
                    $patient->age = $consultation->patient_age;
                    $dirty = true;
                }
                if ($this->isUnknownValue($patient->gender) && !$this->isUnknownValue($consultation->patient_gender)) {
                    $patient->gender = $consultation->patient_gender;
                    $dirty = true;
                }
                if ($dirty) {
                    $patient->save();
                }
            }
            return;
        }

        $patient = null;

        if ($consultation->mrn) {
            $patient = User::where('role', User::ROLE_PATIENT)
                ->where('mrn', $consultation->mrn)
                ->first();
        }

        if (!$patient) {
            $doctor = auth()->user();
            $nameNorm = $this->normalizePatientName($consultation->patient_name);
            $ageNorm = $this->normalizePatientAge($consultation->patient_age);
            $genderNorm = $this->normalizePatientGender($consultation->patient_gender);
            $hospitalAdminId = $this->resolveHospitalAdminIdForUser($doctor);

            if ($nameNorm !== '' && $ageNorm !== '' && $genderNorm !== '' && $nameNorm !== 'unknown') {
                $matchQuery = User::where('role', User::ROLE_PATIENT);
                if ($hospitalAdminId !== null) {
                    $matchQuery->where('hospital_admin_id', $hospitalAdminId);
                }
                $matchQuery->whereRaw('LOWER(TRIM(name)) = ?', [$nameNorm])
                    ->where(function ($w) use ($ageNorm) {
                        $w->where('age', $ageNorm)->orWhere('age', 'like', $ageNorm . '%');
                    })
                    ->whereRaw('LOWER(TRIM(gender)) = ?', [$genderNorm]);
                $patient = $matchQuery->first();
            }

            if ($patient) {
                $mrnResolved = $patient->mrn ?: ($consultation->mrn ?: $this->generateUniqueMrn());
                if (!$patient->mrn) {
                    $patient->mrn = $mrnResolved;
                    $patient->save();
                }
                $consultation->mrn = $mrnResolved;
            } else {
                $mrn = $consultation->mrn ?: $this->generateUniqueMrn();

                $emailBase = 'patient+' . $mrn;
                $email = $emailBase . '@noemail.local';
                $suffix = 1;
                while (User::where('email', $email)->exists()) {
                    $email = $emailBase . '+' . $suffix . '@noemail.local';
                    $suffix++;
                }

                $patient = User::create([
                    'name' => $consultation->patient_name ?: 'New Patient',
                    'email' => $email,
                    'password' => Str::random(32),
                    'role' => User::ROLE_PATIENT,
                    'status' => User::STATUS_ACTIVE,
                    'mrn' => $mrn,
                    'age' => $consultation->patient_age,
                    'gender' => $consultation->patient_gender,
                    'hospital_admin_id' => $hospitalAdminId,
                ]);
                if (!$consultation->mrn) {
                    $consultation->mrn = $mrn;
                }
            }
        }

        $consultation->patient_id = $patient->id;
        $consultation->save();
    }

    private function resolveHospitalAdminIdForUser(?User $user): ?int
    {
        if (!$user) {
            return null;
        }
        if (method_exists($user, 'isHospitalAdmin') && $user->isHospitalAdmin()) {
            return $user->id;
        }
        return $user->hospital_admin_id ?: null;
    }

    private function ensureDoctorPatientAccess(Consultation $c): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if (!$c->patient_id) {
            $this->ensurePatientAccount($c);
        }

        if (($user->isDoctor() || $user->isHospitalAdmin()) && !$c->doctor_id) {
            $c->doctor_id = $user->id;
            $c->save();
        }

        if (!$c->patient_id) {
            return;
        }
        $patient = User::find($c->patient_id);
        if (!$patient) {
            return;
        }

        $hospitalAdminId = $this->resolveHospitalAdminIdForUser($user);
        if ($hospitalAdminId !== null && !$patient->hospital_admin_id) {
            $patient->hospital_admin_id = $hospitalAdminId;
            $patient->save();
        }

        if ($user->isDoctor()) {
            $pivotAttrs = ['hospital_admin_id' => $hospitalAdminId];
            $user->assignedPatients()->syncWithoutDetaching([
                $patient->id => $pivotAttrs,
            ]);
        }
    }

    private function runAiDiagnosis($c, $chatHistory, $input, $mode, $expectPrescription = false) {
        $usedCache = false;
        $cacheSignatureHash = null;
        $cacheSignaturePayload = null;
        $aiReply = '';
        $sources = [];
        $modelName = 'Unknown';
        $jsonObj = null;

        if ($mode === 'final' && $expectPrescription) {
            [$cacheSignatureHash, $cacheSignaturePayload] = $this->buildFinalPrescriptionSignature($c);
            $cacheRow = AiPrescriptionCache::query()->where('signature_hash', $cacheSignatureHash)->first();
            if ($cacheRow && is_array($cacheRow->prescription_data)) {
                $aiReply = (string) ($cacheRow->ai_analysis ?? '');
                $sources = [];
                $modelName = $this->cleanModelName((string) ($cacheRow->model ?? 'Cache'));
                $jsonObj = $cacheRow->prescription_data;
                $c->prescription_data = $jsonObj;
                $usedCache = true;
            }
        }

        if (!$usedCache) {
            try {
                $aiUrl = config('services.ai_service.url');
                if (empty($aiUrl)) {
                    throw new \RuntimeException('AI service URL is not configured.');
                }
                $start = microtime(true);
                $response = Http::timeout(120)->post($aiUrl . '/chat', [
                    'current_input' => $input,
                    'history' => $chatHistory,
                    'mode' => $mode,
                    'patient_age' => $c->patient_age ?? 'Unknown',
                    'patient_gender' => $c->patient_gender ?? 'Unknown',
                    'patient_symptoms' => $c->symptoms ?? ''
                ]);
                $data = $response->json();
                $aiReply = $data['response'] ?? "Error connecting to AI.";
                $sources = $data['sources'] ?? [];
                $modelName = $this->cleanModelName($data['model'] ?? "Unknown");
                Log::info('ai.chat', [
                    'consultation_id' => $c->id,
                    'mode' => $mode,
                    'model' => $modelName,
                    'duration_ms' => round((microtime(true) - $start) * 1000),
                    'sources_count' => count($sources),
                    'ok' => $response->successful(),
                ]);
                if (!$response->successful()) {
                    session()->flash('error', 'AI service returned an error. Please try again.');
                }
            } catch (\Exception $e) {
                $aiReply = "System Error: " . $e->getMessage();
                $sources = [];
                $modelName = "Error";
                Log::error('ai.chat.error', [
                    'consultation_id' => $c->id,
                    'mode' => $mode,
                    'error' => $e->getMessage(),
                ]);
            }

            $jsonObj = $this->extractPrescriptionJson($aiReply, $expectPrescription);
            if (is_array($jsonObj)) {
                $jsonObj = $this->validateAndFixPrescription($jsonObj, $c);
                $c->prescription_data = $jsonObj;
                $aiReply = $this->stripPrescriptionBlock($aiReply);
                $aiReply = rtrim($aiReply) . "\n\n✅ **Prescription Updated**. Please verify.";

                $pharmacists = User::where('role', User::ROLE_PHARMACIST)->get();
                foreach ($pharmacists as $p) {
                    $p->notify(new PrescriptionGenerated($c));
                }

                if (!empty($jsonObj['investigations'])) {
                    $labAssistants = User::where('role', User::ROLE_LAB_ASSISTANT)->get();
                    foreach ($labAssistants as $la) {
                        $la->notify(new LabInvestigationRequested($c));
                    }
                }
                Log::info("Prescription updated successfully for ID: " . $c->id);
            } elseif ($expectPrescription) {
                Log::warning("AI did not return structured prescription. Response snippet: " . substr($aiReply, 0, 250));
                session()->flash('error', 'AI did not return a structured prescription. Please try again.');
            }
        }

        if ($mode === 'final' && $expectPrescription && is_array($jsonObj) && !$usedCache && $cacheSignatureHash && is_array($cacheSignaturePayload)) {
            AiPrescriptionCache::query()->updateOrCreate(
                ['signature_hash' => $cacheSignatureHash],
                [
                    'signature_payload' => $cacheSignaturePayload,
                    'model' => $modelName,
                    'ai_analysis' => $aiReply,
                    'prescription_data' => $jsonObj,
                ]
            );
        }

        if ($usedCache && is_array($jsonObj)) {
            if (trim((string) $aiReply) === '') {
                $aiReply = "✅ **Prescription Loaded from Cache**. Please verify.";
            }
        }

        if ($mode === 'final') {
            $c->status = 'finished';
            $c->is_finalized = true;
            $c->ai_analysis = $aiReply;
            $c->save(); // Save parsing results
        } else {
            $c->save(); // Save updated prescription data if any
        }

        if (is_array($jsonObj)) {
            app(PrescriptionDispenseSyncService::class)->syncForConsultation($c);
        }

        $c->ai_sources = $sources;
        $this->appendAndSave($c, $chatHistory, $aiReply, $modelName, $sources);
        
        if ($mode === 'final' && !empty($c->prescription_data)) {
            return redirect()->route('prescription.edit', $c->id);
        }
        
        return redirect()->back();
    }

    private function buildFinalPrescriptionSignature(Consultation $c): array
    {
        $age = $this->normalizeAiSignatureText((string) ($c->patient_age ?? ''));
        $gender = $this->normalizeAiSignatureText((string) ($c->patient_gender ?? ''));
        $symptoms = $this->normalizeAiSignatureText((string) ($c->symptoms ?? ''));
        $historyHash = $this->buildChatHistorySignatureHash($c);
        $labsHash = $this->buildLabReportsSignatureHash($c);
        $version = 'v2';
        $payload = [
            'version' => $version,
            'age' => $age,
            'gender' => $gender,
            'symptoms' => $symptoms,
            'history_hash' => $historyHash,
            'labs_hash' => $labsHash,
        ];
        $hash = hash('sha256', json_encode($payload));
        return [$hash, $payload];
    }

    private function buildChatHistorySignatureHash(Consultation $c): string
    {
        $history = $c->chat_history ?? null;
        if (!is_array($history)) {
            return hash('sha256', '');
        }

        $parts = [];
        foreach ($history as $msg) {
            if (!is_array($msg)) {
                continue;
            }
            $role = strtolower(trim((string) ($msg['role'] ?? '')));
            $content = (string) ($msg['content'] ?? '');
            if ($content === '') {
                continue;
            }

            $isLabData = stripos($content, '[SYSTEM: LAB DATA') !== false;
            if ($role !== 'user' && !$isLabData) {
                continue;
            }

            $norm = $this->normalizeAiSignatureText($content);
            if ($norm === '') {
                continue;
            }
            $parts[] = $role . ':' . $norm;
            if (count($parts) >= 60) {
                break;
            }
        }

        return hash('sha256', implode('|', $parts));
    }

    private function buildLabReportsSignatureHash(Consultation $c): string
    {
        try {
            $reports = $c->labReports()->get(['file_path', 'notes', 'id']);
        } catch (\Throwable $e) {
            return hash('sha256', '');
        }

        if (!$reports || $reports->isEmpty()) {
            return hash('sha256', '');
        }

        $items = [];
        foreach ($reports as $r) {
            $path = (string) ($r->file_path ?? '');
            $base = $this->normalizeAiSignatureText(basename($path));
            $notes = $this->normalizeAiSignatureText((string) ($r->notes ?? ''));
            $items[] = $base . ':' . $notes . ':' . (string) ($r->id ?? '');
        }
        sort($items);
        return hash('sha256', implode('|', $items));
    }

    private function normalizeAiSignatureText(string $text): string
    {
        $t = strtolower(trim((string) $text));
        $t = preg_replace('/\s+/', ' ', $t);
        $t = preg_replace('/[^a-z0-9\s\-\+\/]/', '', $t);
        return trim((string) $t);
    }

    private function buildFinalConsultPrompt(Consultation $c, string $userMessage, array $chatHistory): string
    {
        $schema = '{
    "diagnosis": "Final Diagnosis",
    "clinical_notes": "C/O: ...\nO/E: ...",
    "investigations": "CBC, CRP, CXR",
    "medicines": [
        {"name": "Tab. Paracetamol", "dosage": "500mg", "frequency": "1-0-1", "duration": "5 days", "instruction": "After food"}
    ],
    "advice": "Advice and follow-up..."
}';

        $currentRx = $c->prescription_data ? json_encode($c->prescription_data) : "None";
        $primarySymptoms = (string) ($c->symptoms ?? '');
        $secondarySymptoms = $this->extractSecondarySymptomsFromHistory($chatHistory);
        return "Generate the Final Diagnosis Report for an experienced clinician (concise, note-style, no patient-facing language).\n\n"
            . "PRIMARY SYMPTOMS (intake): " . ($primarySymptoms !== '' ? $primarySymptoms : 'Unknown') . "\n"
            . "SECONDARY / ASSOCIATED SYMPTOMS (from conversation): " . ($secondarySymptoms !== '' ? $secondarySymptoms : 'None captured') . "\n\n"
            . "Then generate the FINAL prescription JSON in the following REQUIRED FORMAT:\n"
            . "[PRESCRIPTION_START]\n"
            . $schema . "\n"
            . "[PRESCRIPTION_END]\n\n"
            . "INSTRUCTIONS:\n"
            . "1. Use PRIMARY symptoms and ALL secondary symptoms across the chat; do not ignore minor symptoms.\n"
            . "2. Keep chronic meds if appropriate.\n"
            . "3. Clinical notes must include BOTH sections with labels: 'C/O:' and 'O/E:'.\n"
            . "4. Investigations must be comma-separated short forms as doctors write (e.g., 'CBC, CRP, CXR').\n"
            . "5. Medicine 'name' must be SHORT dosage-form prefix + generic composition (e.g., 'Tab. Paracetamol', 'Syr. Cetirizine').\n"
            . "6. Do NOT use vague terms like 'Cough', 'Antihistamine', 'Painkiller' as medicine names.\n"
            . "7. Prefer medicines that are supported by the provided context/guideline documents; if not available, use standard first-line generics.\n"
            . "8. Do NOT add explanations inside the JSON; keep fields clinically brief.\n"
            . "9. The content INSIDE the tags must be valid JSON matching the schema.\n"
            . "10. Do NOT wrap the tags in quotes.\n"
            . "11. If epigastric/upper abdominal pain, dyspepsia/GERD/heartburn/reflux, nausea/vomiting, or troublesome gas/bloating are present, include appropriate symptom relief (e.g., Tab. Pantoprazole once daily before breakfast; consider Tab. Simethicone after food) with clear duration.\n"
            . "12. Do NOT add antibiotics unless there is clear bacterial indication in the notes or labs.\n"
            . "13. Ensure the medicines directly match the C/O and O/E; do not omit high-yield symptom control.\n\n"
            . "14. NON-CREATIVE MODE: Provide a single best, conservative regimen; do not invent diagnoses or add optional alternatives.\n"
            . "15. COMPLETENESS: If symptoms clearly indicate common supportive medicines (fever/pain, nausea/vomiting, acidity/gas, diarrhea/ORS if present), include them with timing and duration.\n\n"
            . "DOCTOR NOTE (if any): " . $userMessage . "\n\n"
            . "CURRENT PRESCRIPTION (if any):\n" . $currentRx;
    }

    private function extractSecondarySymptomsFromHistory(array $chatHistory): string
    {
        $texts = [];
        foreach ($chatHistory as $m) {
            if (!is_array($m)) {
                continue;
            }
            if (($m['role'] ?? null) !== 'user') {
                continue;
            }
            $content = trim((string) ($m['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            if (str_contains($content, '[SYSTEM: LAB DATA START]') || str_contains($content, '📄 **UPLOADED REPORT:**')) {
                continue;
            }
            $content = strip_tags($content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim((string) $content);
            if ($content === '') {
                continue;
            }
            $texts[] = $content;
        }

        $texts = array_values(array_unique($texts));
        if (count($texts) > 10) {
            $texts = array_slice($texts, -10);
        }
        $joined = implode(' | ', $texts);
        if (strlen($joined) > 800) {
            $joined = substr($joined, 0, 800);
        }
        return trim($joined);
    }

    private function stripPrescriptionBlock(string $text): string
    {
        $text = preg_replace('/\[PRESCRIPTION_START\][\s\S]*?\[PRESCRIPTION_END\]/', '', $text);
        return trim($text);
    }

    private function extractPrescriptionJson(string $aiReply, bool $expectPrescription): ?array
    {
        if (preg_match('/\[PRESCRIPTION_START\](.*?)\[PRESCRIPTION_END\]/s', $aiReply, $matches)) {
            $jsonStr = trim($matches[1]);
            $jsonStr = preg_replace('/^```json\s*/', '', $jsonStr);
            $jsonStr = preg_replace('/^```\s*/', '', $jsonStr);
            $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);
            $jsonObj = json_decode($jsonStr, true);
            return is_array($jsonObj) ? $jsonObj : null;
        }

        if (!$expectPrescription) {
            return null;
        }

        if (preg_match('/```json\s*([\s\S]*?)```/i', $aiReply, $m) || preg_match('/```\s*([\s\S]*?)```/i', $aiReply, $m)) {
            $candidate = trim($m[1]);
            $jsonObj = json_decode($candidate, true);
            if (is_array($jsonObj) && isset($jsonObj['medicines'])) {
                return $jsonObj;
            }
        }

        $len = strlen($aiReply);
        $starts = [];
        for ($i = 0; $i < $len; $i++) {
            if ($aiReply[$i] === '{') {
                $starts[] = $i;
            }
        }
        foreach ($starts as $start) {
            $depth = 0;
            for ($j = $start; $j < $len; $j++) {
                $ch = $aiReply[$j];
                if ($ch === '{') $depth++;
                if ($ch === '}') $depth--;
                if ($depth === 0 && $j > $start) {
                    $candidate = substr($aiReply, $start, $j - $start + 1);
                    $jsonObj = json_decode($candidate, true);
                    if (is_array($jsonObj) && isset($jsonObj['medicines'])) {
                        return $jsonObj;
                    }
                    break;
                }
            }
        }

        return null;
    }

    private function validateAndFixPrescription(array $rx, Consultation $c): array
    {
        $rx['diagnosis'] = isset($rx['diagnosis']) ? (string) $rx['diagnosis'] : '';
        $rx['clinical_notes'] = isset($rx['clinical_notes']) ? (string) $rx['clinical_notes'] : '';
        $rx['investigations'] = isset($rx['investigations']) ? (string) $rx['investigations'] : '';
        $rx['advice'] = isset($rx['advice']) ? (string) $rx['advice'] : '';

        $meds = $rx['medicines'] ?? [];
        if (!is_array($meds)) {
            $meds = [];
        }
        $normalized = [];
        foreach ($meds as $m) {
            if (!is_array($m)) {
                continue;
            }
            $normalized[] = [
                'name' => isset($m['name']) ? (string) $m['name'] : '',
                'dosage' => isset($m['dosage']) ? (string) $m['dosage'] : '',
                'frequency' => isset($m['frequency']) ? (string) $m['frequency'] : '',
                'duration' => isset($m['duration']) ? (string) $m['duration'] : '',
                'instruction' => isset($m['instruction']) ? (string) $m['instruction'] : '',
            ];
        }

        $normalizeName = function (string $name): string {
            $n = strtolower(trim($name));
            $n = preg_replace('/\s+/', ' ', $n);
            $n = preg_replace('/^(tab|cap|syr|inj|drop|drops|crm|oint|gel|soln|susp)\.\s*/i', '', $n);
            return trim((string) $n);
        };

        $filtered = [];
        $seen = [];
        foreach ($normalized as $m) {
            $name = trim((string) ($m['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = $normalizeName($name);
            if ($key === '' || in_array($key, ['painkiller', 'antibiotic', 'antihistamine', 'cough', 'cough syrup', 'antacid'], true)) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $m['name'] = $name;
            $filtered[] = $m;
            if (count($filtered) >= 20) {
                break;
            }
        }
        $normalized = $filtered;

        $symptomsText = strtolower((string) ($c->symptoms ?? ''));
        $notesText = strtolower((string) ($rx['clinical_notes'] ?? ''));
        $combined = $symptomsText . "\n" . $notesText;

        $hasUpperAbdominal = str_contains($combined, 'upper abdominal') || str_contains($combined, 'epigastric') || str_contains($combined, 'dyspeps') || str_contains($combined, 'heartburn') || str_contains($combined, 'reflux') || str_contains($combined, 'acidity');
        $hasGasBloating = str_contains($combined, 'gas') || str_contains($combined, 'bloat') || str_contains($combined, 'flatulen');

        $names = array_map(function ($m) use ($normalizeName) {
            return $normalizeName((string) ($m['name'] ?? ''));
        }, $normalized);

        $hasPpi = false;
        $hasAntacid = false;
        $hasSimethicone = false;
        foreach ($names as $n) {
            if (str_contains($n, 'pantoprazole') || str_contains($n, 'omeprazole') || str_contains($n, 'rabeprazole') || str_contains($n, 'esomeprazole') || str_contains($n, 'lansoprazole')) {
                $hasPpi = true;
            }
            if (str_contains($n, 'aluminium') || str_contains($n, 'aluminum') || str_contains($n, 'magnesium hydroxide') || str_contains($n, 'antacid')) {
                $hasAntacid = true;
            }
            if (str_contains($n, 'simethicone')) {
                $hasSimethicone = true;
            }
        }

        if ($hasUpperAbdominal && !$hasPpi && !$hasAntacid) {
            $normalized[] = [
                'name' => 'Tab. Pantoprazole',
                'dosage' => '40mg',
                'frequency' => '1-0-0',
                'duration' => '5 days',
                'instruction' => 'Before breakfast',
            ];
        }

        if ($hasGasBloating && !$hasSimethicone) {
            $normalized[] = [
                'name' => 'Tab. Simethicone',
                'dosage' => '80mg',
                'frequency' => '1-1-1',
                'duration' => '3 days',
                'instruction' => 'After food',
            ];
        }

        $filtered = [];
        $seen = [];
        foreach ($normalized as $m) {
            $name = trim((string) ($m['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = $normalizeName($name);
            if ($key === '' || in_array($key, ['painkiller', 'antibiotic', 'antihistamine', 'cough', 'cough syrup', 'antacid'], true)) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $m['name'] = $name;
            $filtered[] = $m;
            if (count($filtered) >= 20) {
                break;
            }
        }

        if (Medicine::query()->where('is_active', true)->exists()) {
            $knownFiltered = [];
            $knownCache = [];
            foreach ($filtered as $m) {
                $name = trim((string) ($m['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $key = $normalizeName($name);
                if ($key === '') {
                    continue;
                }
                if (!array_key_exists($key, $knownCache)) {
                    $knownCache[$key] =
                        Medicine::query()->where('is_active', true)->search($key, 'composition')->limit(1)->exists()
                        || Medicine::query()->where('is_active', true)->search($key, 'name')->limit(1)->exists()
                        || Medicine::query()->where('is_active', true)->search($key, 'all')->limit(1)->exists();
                }
                if ($knownCache[$key]) {
                    $knownFiltered[] = $m;
                }
            }
            $filtered = $knownFiltered;
        }

        $rx['medicines'] = $filtered;
        return $rx;
    }

    public function editPrescription($id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);
        $data = $c->prescription_data ?? ['medicines' => [], 'advice' => '', 'diagnosis' => '', 'clinical_notes' => '', 'investigations' => ''];
        if (is_string($data) && $data !== '') {
            $data = json_decode($data, true) ?: [];
        }
        $data = is_array($data) ? $data : [];
        $data['medicines'] = is_array($data['medicines'] ?? null) ? $data['medicines'] : [];
        $normalizedMeds = [];
        foreach ($data['medicines'] as $m) {
            if (!is_array($m)) {
                continue;
            }
            $composition = trim((string) ($m['composition_name'] ?? $m['name'] ?? ''));
            $brandName = trim((string) ($m['brand_name'] ?? ''));
            $m['composition_name'] = $composition;
            $m['brand_name'] = $brandName;
            $m['brand_medicine_id'] = $m['brand_medicine_id'] ?? '';
            $m['brand_strength'] = $m['brand_strength'] ?? '';
            $m['brand_dosage_form'] = $m['brand_dosage_form'] ?? '';
            $m['brand_composition_text'] = $m['brand_composition_text'] ?? '';
            if (empty($m['name'])) {
                $m['name'] = $brandName !== '' ? $brandName : $composition;
            }
            $normalizedMeds[] = $m;
        }
        $data['medicines'] = $normalizedMeds;
        
        $medicineDB = Medicine::where('is_active', true)
            ->orderBy('name')
            ->orderBy('strength')
            ->limit(3000)
            ->get(['name', 'strength', 'type', 'therapeutic_class'])
            ->map(function ($m) {
                return [
                    'name' => $m->name,
                    'strength' => $m->strength,
                    'type' => $m->type,
                    'class' => $m->therapeutic_class,
                ];
            })->toArray();

        $extendedPath = base_path('../python_service/data/medicines_extended.json');
        $basePath = base_path('../python_service/data/medicines_nlem.json');
        if (empty($medicineDB)) {
            if (file_exists($extendedPath)) {
                $medicineDB = json_decode(file_get_contents($extendedPath), true);
            } elseif (file_exists($basePath)) {
                $medicineDB = json_decode(file_get_contents($basePath), true);
            }
        }

        $pharmacists = User::where('role', User::ROLE_PHARMACIST)->get();
        
        return view('prescription_edit', ['c' => $c, 'data' => $data, 'medicines_db' => $medicineDB, 'pharmacists' => $pharmacists]);
    }

    public function updatePrescription(Request $request, $id)
    {
        $c = Consultation::findOrFail($id);
        $this->authorizeConsultationAccess($c);
        $this->ensureDoctorPatientAccess($c);
        
        if ($request->has('pharmacist_id')) {
            $c->pharmacist_id = $request->pharmacist_id;
        }

        $medicines = $request->medicines ?? [];
        $medicines = is_array($medicines) ? $medicines : [];
        $normalized = [];
        foreach ($medicines as $m) {
            if (!is_array($m)) {
                continue;
            }
            $composition = trim((string) ($m['composition_name'] ?? $m['name'] ?? ''));
            $brandName = trim((string) ($m['brand_name'] ?? ''));
            $m['composition_name'] = $composition;
            $m['brand_name'] = $brandName;
            $m['brand_medicine_id'] = $m['brand_medicine_id'] ?? null;
            $m['brand_strength'] = $m['brand_strength'] ?? null;
            $m['brand_dosage_form'] = $m['brand_dosage_form'] ?? null;
            $m['brand_composition_text'] = $m['brand_composition_text'] ?? null;
            $m['name'] = $brandName !== '' ? $brandName : $composition;
            $normalized[] = $m;
        }

        $c->prescription_data = [
            'diagnosis' => $request->diagnosis,
            'clinical_notes' => $request->clinical_notes,
            'investigations' => $request->investigations,
            'medicines' => $normalized,
            'advice' => $request->advice
        ];
        $c->save();
        app(PrescriptionDispenseSyncService::class)->syncForConsultation($c);

        // --- NOTIFICATIONS START ---
        if ($c->assignedPharmacist) {
            $c->assignedPharmacist->notify(new PrescriptionGenerated($c));
        } else {
            $pharmacists = User::where('role', User::ROLE_PHARMACIST)->get();
            foreach ($pharmacists as $p) {
                $p->notify(new PrescriptionGenerated($c));
            }
        }

        if (!empty($request->investigations)) {
            $labAssistants = User::where('role', User::ROLE_LAB_ASSISTANT)->get();
            foreach ($labAssistants as $la) {
                $la->notify(new LabInvestigationRequested($c));
            }
        }
        // --- NOTIFICATIONS END ---
        
        return redirect()->route('prescription.generate', $id);
    }

    private function appendAndSave($consultation, $history, $reply, $modelName, $sources) {
        try {
            $history[] = ['role' => 'assistant', 'content' => $reply, 'model' => $modelName, 'sources' => $sources];
            $consultation->chat_history = json_encode($history);
            $consultation->save();
        } catch (\Throwable $e) {
            session()->flash('error', 'Database error while saving chat history.');
        }
    }

    private function parsePatientDetails($text) {
        preg_match('/(\d+)\s*(?:years|year|yrs|yr|yo|y\.o\.?|months|mo|m)\b/i', $text, $ageMatch);
        $age = $ageMatch[0] ?? null;
        preg_match('/\b(male|female|man|woman|boy|girl|gentleman|lady)\b/i', $text, $genderMatch);
        $gender = $genderMatch[0] ?? null;

        // NEW: Email
        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emailMatch);
        $email = $emailMatch[0] ?? null;

        // NEW: MRN (10 digits)
        preg_match('/\b\d{10}\b/', $text, $mrnMatch);
        $mrn = $mrnMatch[0] ?? null;

        if (($age && $gender) || $email || $mrn) {
            $cleanText = str_replace([$age ?? '', $gender ?? '', $email ?? '', $mrn ?? ''], '', $text);
            $cleanText = preg_replace('/\b(Patient is|Patient name is|This is|Name is|Patient|is a|is an)\b/i', '', $cleanText);
            $cleanText = trim(preg_replace('/[^\w\s]/', ' ', $cleanText));
            $words = array_values(array_filter(explode(' ', $cleanText)));
            
            if (count($words) >= 1) {
                $namePart = implode(' ', array_slice($words, 0, 2));
                if (strtolower($namePart) === 'patient' || strlen($namePart) > 30) $name = "Patient";
                else $name = ucwords($namePart);
            } else $name = "Patient";
            
            return [
                'has_info' => true, 
                'name' => $name, 
                'age' => $age, 
                'gender' => $gender ? ucfirst($gender) : null, 
                'symptoms' => $text,
                'email' => $email,
                'mrn' => $mrn
            ];
        }
        return ['has_info' => false];
    }

    private function cleanModelName($raw) {
        if (str_contains($raw, 'gemini')) return 'Google Gemini 2.0';
        if (str_contains($raw, 'llama')) return 'Llama 3.3 (70B)';
        if (str_contains($raw, 'deepseek')) return 'DeepSeek R1';
        if (str_contains($raw, 'xiaomi')) return 'Xiaomi MiMo';
        return 'AI Model';
    }
}
