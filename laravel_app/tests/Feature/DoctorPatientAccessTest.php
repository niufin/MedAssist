<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorPatientAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_first_prescription_save_assigns_patient_to_doctor_and_hospital_and_allows_shared_access(): void
    {
        $hospital = User::factory()->create([
            'role' => User::ROLE_HOSPITAL_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $doctor1 = User::factory()->create([
            'name' => 'Doctor One',
            'role' => User::ROLE_DOCTOR,
            'status' => User::STATUS_ACTIVE,
            'hospital_admin_id' => $hospital->id,
        ]);
        $doctor2 = User::factory()->create([
            'name' => 'Doctor Two',
            'role' => User::ROLE_DOCTOR,
            'status' => User::STATUS_ACTIVE,
            'hospital_admin_id' => $hospital->id,
        ]);

        $c1 = Consultation::create([
            'symptoms' => 'Fever',
            'patient_name' => 'Same Patient',
            'patient_age' => '30',
            'patient_gender' => 'Male',
            'status' => 'consulting',
            'doctor_id' => $doctor1->id,
        ]);

        $resp1 = $this->actingAs($doctor1)->post('/prescription/update/' . $c1->id, [
            'diagnosis' => 'Dx',
            'clinical_notes' => "C/O: Fever\nO/E: Stable",
            'investigations' => '',
            'advice' => '',
            'medicines' => [
                [
                    'composition_name' => 'Tab. Paracetamol',
                    'brand_name' => '',
                    'dosage' => '500mg',
                    'frequency' => '1-0-1',
                    'duration' => '3 days',
                    'instruction' => 'After food',
                ],
            ],
        ]);
        $resp1->assertRedirect();

        $c1->refresh();
        $this->assertNotNull($c1->patient_id);
        $this->assertNotNull($c1->mrn);

        $patient = User::find($c1->patient_id);
        $this->assertNotNull($patient);
        $this->assertSame(User::ROLE_PATIENT, $patient->role);
        $this->assertSame($hospital->id, $patient->hospital_admin_id);

        $this->assertDatabaseHas('doctor_patient_access', [
            'doctor_id' => $doctor1->id,
            'patient_id' => $patient->id,
        ]);

        $c2 = Consultation::create([
            'symptoms' => 'Fever again',
            'patient_name' => 'Same Patient',
            'patient_age' => '30',
            'patient_gender' => 'Male',
            'status' => 'consulting',
            'doctor_id' => $doctor2->id,
            'mrn' => '9999999999',
        ]);

        $resp2 = $this->actingAs($doctor2)->post('/prescription/update/' . $c2->id, [
            'diagnosis' => 'Dx2',
            'clinical_notes' => "C/O: Fever\nO/E: Stable",
            'investigations' => '',
            'advice' => '',
            'medicines' => [
                [
                    'composition_name' => 'Tab. Paracetamol',
                    'brand_name' => '',
                    'dosage' => '500mg',
                    'frequency' => '1-0-1',
                    'duration' => '3 days',
                    'instruction' => 'After food',
                ],
            ],
        ]);
        $resp2->assertRedirect();

        $c2->refresh();
        $this->assertSame($patient->id, $c2->patient_id);
        $this->assertSame($patient->mrn, $c2->mrn);

        $this->assertDatabaseHas('doctor_patient_access', [
            'doctor_id' => $doctor2->id,
            'patient_id' => $patient->id,
        ]);

        $this->actingAs($doctor2)->get('/prescription/edit/' . $c1->id)->assertOk();
        $raw = $this->actingAs($doctor2)->get('/prescription/preview/raw/' . $c1->id);
        $raw->assertOk();
        $raw->assertSee('Doctor One');
    }
}

