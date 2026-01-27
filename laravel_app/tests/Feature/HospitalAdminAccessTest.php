<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HospitalAdminAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hospital_admin_sees_own_and_doctor_consultations_and_can_edit_prescription(): void
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
            'name' => 'Doctor Under Hospital',
        ]);

        $patient = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'status' => User::STATUS_ACTIVE,
            'hospital_admin_id' => $hospital->id,
            'name' => 'Scoped Patient',
        ]);

        $adminConsultation = Consultation::create([
            'symptoms' => 'Admin created',
            'status' => 'consulting',
            'doctor_id' => $hospital->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
        ]);

        $doctorConsultation = Consultation::create([
            'symptoms' => 'Doctor created',
            'status' => 'consulting',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
        ]);

        $dashboard = $this->actingAs($hospital)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee((string) $adminConsultation->id);
        $dashboard->assertSee((string) $doctorConsultation->id);

        $this->actingAs($hospital)->get('/prescription/edit/' . $adminConsultation->id)->assertOk();
        $this->actingAs($hospital)->get('/prescription/edit/' . $doctorConsultation->id)->assertOk();
    }
}

