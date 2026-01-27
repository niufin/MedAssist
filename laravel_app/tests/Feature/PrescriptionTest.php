<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Consultation;
use App\Models\LabReport;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\User;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_download_succeeds_with_final_analysis()
    {
        $doctor = $this->fakeUser(User::ROLE_DOCTOR);

        $c = new Consultation();
        $c->symptoms = 'Fever and cough';
        $c->ai_analysis = "# Diagnosis\n\n- Viral upper respiratory infection\n\n## Treatment\n- Rest\n- Hydration\n- Paracetamol 500mg";
        $c->patient_name = 'John Doe';
        $c->patient_age = '32 years';
        $c->patient_gender = 'Male';
        $c->status = 'finished';
        $c->doctor_id = $doctor->id;
        $c->save();

        $response = $this->actingAs($doctor)->get(route('prescription.generate', $c->id));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_super_admin_can_access_key_routes_without_403()
    {
        $superAdmin = $this->fakeUser(User::ROLE_SUPER_ADMIN);

        $patientsResponse = $this->actingAs($superAdmin)->get(route('doctor.patients.index'));
        $patientsResponse->assertStatus(200);

        $pharmacistDashboard = $this->actingAs($superAdmin)->get(route('pharmacist.dashboard'));
        $pharmacistDashboard->assertStatus(200);

        $labDashboard = $this->actingAs($superAdmin)->get(route('lab.dashboard'));
        $labDashboard->assertStatus(200);

        $patientDashboard = $this->actingAs($superAdmin)->get(route('patient.dashboard'));
        $patientDashboard->assertStatus(200);

        $hospitalAdmin = $this->fakeUser(User::ROLE_HOSPITAL_ADMIN);
        $doctor = $this->fakeUser(User::ROLE_DOCTOR);
        $doctor->hospital_admin_id = $hospitalAdmin->id;
        $doctor->save();

        $consultation = Consultation::create([
            'symptoms' => 'Test symptoms',
            'doctor_id' => $doctor->id,
            'patient_name' => 'Patient',
            'patient_age' => '30',
            'patient_gender' => 'Male',
            'status' => 'finished',
            'ai_analysis' => 'ok',
            'prescription_data' => json_encode(['investigations' => 'CBC']),
        ]);

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
            'uploaded_by' => $superAdmin->id,
        ]);

        $labReportView = $this->actingAs($superAdmin)->get(route('lab.report.view', $report->id));
        $labReportView->assertStatus(200);
    }

    public function test_receiving_purchase_order_with_null_medicine_id_creates_medicine_and_stock()
    {
        $superAdmin = $this->fakeUser(User::ROLE_SUPER_ADMIN);
        $hospitalAdmin = $this->fakeUser(User::ROLE_HOSPITAL_ADMIN);

        $store = PharmacyStore::create([
            'hospital_admin_id' => $hospitalAdmin->id,
            'name' => 'Pharmacy',
        ]);

        $order = PurchaseOrder::create([
            'pharmacy_store_id' => $store->id,
            'supplier_id' => null,
            'po_no' => 'PO' . $store->id . '-20260119-0001',
            'status' => 'ordered',
            'ordered_at' => now(),
            'notes' => null,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'medicine_id' => null,
            'medicine_name' => 'Test Medicine',
            'quantity' => 10,
            'unit_cost' => 10,
            'line_total' => 100,
        ]);

        $response = $this->withSession(['active_hospital_admin_id' => $hospitalAdmin->id])
            ->actingAs($superAdmin)
            ->post(route('pharmacy.purchases.orders.receive', $order->id), [
                'items' => [
                    $item->id => [
                        'quantity_received' => 5,
                        'batch_no' => 'B-5678',
                        'expiry_date' => '2026-01-19',
                        'mrp' => 75,
                        'purchase_price' => 56.00,
                        'sale_price' => 75,
                    ],
                ],
            ]);

        $response->assertRedirect(route('pharmacy.inventory.index'));

        $medicine = Medicine::where('name', 'Test Medicine')->first();
        $this->assertNotNull($medicine);

        $batch = StockBatch::where('pharmacy_store_id', $store->id)
            ->where('medicine_id', $medicine->id)
            ->where('batch_no', 'B-5678')
            ->first();

        $this->assertNotNull($batch);
        $this->assertSame(5, (int) $batch->quantity_on_hand);
    }

    public function test_new_consultation_for_existing_patient_backfills_age_and_gender()
    {
        $doctor = $this->fakeUser(User::ROLE_DOCTOR);

        $patient = $this->fakeUser(User::ROLE_PATIENT);
        $patient->mrn = '7172411318';
        $patient->age = null;
        $patient->gender = null;
        $patient->save();

        Consultation::create([
            'patient_id' => $patient->id,
            'mrn' => $patient->mrn,
            'symptoms' => 'Old symptoms',
            'patient_name' => $patient->name,
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'finished',
            'ai_analysis' => 'ok',
            'prescription_data' => ['medicines' => []],
        ]);

        $response = $this->actingAs($doctor)->post(route('doctor.patients.new_consultation', $patient->id));
        $response->assertRedirect();

        $newConsult = Consultation::where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($newConsult);
        $this->assertSame('34', $newConsult->patient_age);
        $this->assertSame('Male', $newConsult->patient_gender);

        $patient->refresh();
        $this->assertSame('34', $patient->age);
        $this->assertSame('Male', $patient->gender);
    }

    public function test_pharmacist_dashboard_lists_consultations_scoped_by_patient_hospital()
    {
        $hospitalAdmin = $this->fakeUser(User::ROLE_HOSPITAL_ADMIN);
        $pharmacist = $this->fakeUser(User::ROLE_PHARMACIST);
        $pharmacist->hospital_admin_id = $hospitalAdmin->id;
        $pharmacist->save();

        $patient = $this->fakeUser(User::ROLE_PATIENT);
        $patient->hospital_admin_id = $hospitalAdmin->id;
        $patient->mrn = '9000000001';
        $patient->save();

        Consultation::create([
            'patient_id' => $patient->id,
            'mrn' => $patient->mrn,
            'symptoms' => 'Symptoms',
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Female',
            'status' => 'finished',
            'ai_analysis' => 'ok',
            'prescription_data' => ['medicines' => [['name' => 'Tab. Paracetamol']]],
        ]);

        $response = $this->actingAs($pharmacist)->get(route('pharmacist.dashboard'));
        $response->assertStatus(200);
        $response->assertSee($patient->name);
    }

    private function fakeUser(string $role = User::ROLE_DOCTOR): User
    {
        $class = config('auth.providers.users.model');
        $user = new $class();
        $user->name = 'Tester ' . $role;
        $user->email = $role . '_' . uniqid() . '@example.com';
        $user->password = bcrypt('secret');
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();
        return $user;
    }
}
