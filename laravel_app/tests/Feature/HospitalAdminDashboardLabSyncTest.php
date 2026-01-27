<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\LabReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HospitalAdminDashboardLabSyncTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hospital_admin_can_open_hospital_dashboard_and_lab_dashboard_and_sync(): void
    {
        $hospital = User::factory()->create([
            'role' => User::ROLE_HOSPITAL_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Hospital Admin',
        ]);

        $doctor = User::factory()->create([
            'role' => User::ROLE_DOCTOR,
            'status' => User::STATUS_ACTIVE,
            'hospital_admin_id' => $hospital->id,
            'name' => 'Doctor',
        ]);

        $patient = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'status' => User::STATUS_ACTIVE,
            'hospital_admin_id' => $hospital->id,
            'name' => 'Patient Name',
        ]);

        $consultation = Consultation::create([
            'symptoms' => 'Fever',
            'status' => 'consulting',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
        ]);

        $this->actingAs($hospital)->get('/hospital/dashboard')->assertOk();
        $lab = $this->actingAs($hospital)->get('/lab/dashboard');
        $lab->assertOk();
        $lab->assertSee($patient->name);

        $relativePath = 'lab_reports/test-report.pdf';
        $absolutePath = storage_path('app/public/' . $relativePath);
        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }
        file_put_contents($absolutePath, '%PDF-1.4 test');

        $report = LabReport::create([
            'consultation_id' => $consultation->id,
            'file_path' => $relativePath,
            'notes' => 'test',
            'uploaded_by' => $hospital->id,
        ]);

        $this->actingAs($hospital)->get(route('lab.report.view', $report->id))->assertOk();

        $needsBackfill = Consultation::create([
            'symptoms' => 'Cough',
            'status' => 'consulting',
            'doctor_id' => $doctor->id,
            'patient_id' => null,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
        ]);

        $this->actingAs($hospital)
            ->from('/dashboard')
            ->get(route('consultations.backfill'))
            ->assertRedirect('/dashboard');

        $needsBackfill->refresh();
        $this->assertSame($patient->id, $needsBackfill->patient_id);
    }
}

