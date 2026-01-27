<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\DispenseItem;
use App\Models\DispenseOrder;
use App\Models\Medicine;
use App\Models\PharmacyInvoice;
use App\Models\PharmacyInvoiceItem;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraMedicinesDispenseInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_extra_medicines_after_dispense_create_new_order_and_new_invoice(): void
    {
        $hospitalAdmin = $this->fakeUser(User::ROLE_HOSPITAL_ADMIN);

        $doctor = $this->fakeUser(User::ROLE_DOCTOR);
        $doctor->hospital_admin_id = $hospitalAdmin->id;
        $doctor->save();

        $patient = $this->fakeUser(User::ROLE_PATIENT);
        $patient->hospital_admin_id = $hospitalAdmin->id;
        $patient->save();

        $pharmacist = $this->fakeUser(User::ROLE_PHARMACIST);
        $pharmacist->hospital_admin_id = $hospitalAdmin->id;
        $pharmacist->save();

        $store = PharmacyStore::create([
            'hospital_admin_id' => $hospitalAdmin->id,
            'name' => 'Pharmacy',
        ]);

        $medA = Medicine::create([
            'name' => 'Tab. Paracetamol',
            'is_active' => true,
        ]);

        $medB = Medicine::create([
            'name' => 'Tab. Cetirizine',
            'is_active' => true,
        ]);

        $consultation = Consultation::create([
            'symptoms' => 'Test symptoms',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
            'status' => 'finished',
            'ai_analysis' => 'ok',
            'prescription_data' => [
                'diagnosis' => 'Test',
                'clinical_notes' => '',
                'investigations' => '',
                'medicines' => [
                    ['name' => $medA->name, 'dosage' => '500mg', 'frequency' => '1-0-1', 'duration' => '3 days', 'instruction' => 'After food'],
                ],
                'advice' => '',
            ],
        ]);

        $oldOrder = DispenseOrder::create([
            'pharmacy_store_id' => $store->id,
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'pharmacist_id' => $pharmacist->id,
            'status' => 'dispensed',
            'dispensed_at' => now(),
        ]);

        $oldItem = DispenseItem::create([
            'dispense_order_id' => $oldOrder->id,
            'medicine_id' => $medA->id,
            'medicine_name' => $medA->name,
            'quantity' => 1,
            'dispensed_quantity' => 1,
            'status' => 'dispensed',
        ]);

        $invoice1 = PharmacyInvoice::create([
            'pharmacy_store_id' => $store->id,
            'dispense_order_id' => $oldOrder->id,
            'invoice_no' => 'PH' . $store->id . '-20260101-0001',
            'patient_id' => $patient->id,
            'subtotal' => 10,
            'discount' => 0,
            'tax' => 0,
            'total' => 10,
            'paid_total' => 0,
            'status' => 'unpaid',
            'issued_at' => now(),
        ]);

        PharmacyInvoiceItem::create([
            'pharmacy_invoice_id' => $invoice1->id,
            'medicine_id' => $medA->id,
            'medicine_name' => $medA->name,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $response = $this->actingAs($doctor)->post(route('prescription.update', $consultation->id), [
            'diagnosis' => 'Test',
            'clinical_notes' => '',
            'investigations' => '',
            'advice' => '',
            'medicines' => [
                ['name' => $medA->name, 'dosage' => '500mg', 'frequency' => '1-0-1', 'duration' => '3 days', 'instruction' => 'After food'],
                ['name' => $medB->name, 'dosage' => '10mg', 'frequency' => '0-0-1', 'duration' => '5 days', 'instruction' => 'At bedtime'],
            ],
        ]);
        $response->assertRedirect();

        $newOrder = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->where('consultation_id', $consultation->id)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($newOrder);
        $this->assertNotSame($oldOrder->id, $newOrder->id);

        $newItems = DispenseItem::where('dispense_order_id', $newOrder->id)->get();
        $this->assertCount(1, $newItems);
        $this->assertSame($medB->name, $newItems->first()->medicine_name);

        $batchB = StockBatch::create([
            'pharmacy_store_id' => $store->id,
            'medicine_id' => $medB->id,
            'batch_no' => 'B-EXTRA',
            'expiry_date' => now()->addYear(),
            'quantity_on_hand' => 50,
            'mrp' => 20,
            'purchase_price' => 10,
            'sale_price' => 15,
        ]);

        $dispenseResponse = $this->actingAs($pharmacist)->post(route('pharmacy.dispense.item.dispense', $newItems->first()->id), [
            'quantity' => 1,
            'stock_batch_id' => $batchB->id,
            'medicine_id' => $medB->id,
        ]);
        $dispenseResponse->assertRedirect();

        $finalizeResponse = $this->actingAs($pharmacist)->post(route('pharmacy.dispense.order.finalize', $newOrder->id));
        $finalizeResponse->assertRedirect();

        $invoice2 = PharmacyInvoice::where('pharmacy_store_id', $store->id)
            ->where('dispense_order_id', $newOrder->id)
            ->first();

        $this->assertNotNull($invoice2);
        $this->assertNotSame($invoice1->id, $invoice2->id);

        $invoice2Items = PharmacyInvoiceItem::where('pharmacy_invoice_id', $invoice2->id)->get();
        $this->assertCount(1, $invoice2Items);
        $this->assertSame($medB->name, $invoice2Items->first()->medicine_name);
    }

    public function test_extra_medicines_before_finalize_append_to_open_order(): void
    {
        $hospitalAdmin = $this->fakeUser(User::ROLE_HOSPITAL_ADMIN);

        $doctor = $this->fakeUser(User::ROLE_DOCTOR);
        $doctor->hospital_admin_id = $hospitalAdmin->id;
        $doctor->save();

        $patient = $this->fakeUser(User::ROLE_PATIENT);
        $patient->hospital_admin_id = $hospitalAdmin->id;
        $patient->save();

        $store = PharmacyStore::create([
            'hospital_admin_id' => $hospitalAdmin->id,
            'name' => 'Pharmacy',
        ]);

        $medA = Medicine::create([
            'name' => 'Tab. Paracetamol',
            'is_active' => true,
        ]);

        $medB = Medicine::create([
            'name' => 'Tab. Cetirizine',
            'is_active' => true,
        ]);

        $consultation = Consultation::create([
            'symptoms' => 'Test symptoms',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_age' => '30',
            'patient_gender' => 'Male',
            'status' => 'finished',
            'ai_analysis' => 'ok',
            'prescription_data' => [
                'diagnosis' => 'Test',
                'clinical_notes' => '',
                'investigations' => '',
                'medicines' => [
                    ['name' => $medA->name],
                ],
                'advice' => '',
            ],
        ]);

        $openOrder = DispenseOrder::create([
            'pharmacy_store_id' => $store->id,
            'consultation_id' => $consultation->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'pharmacist_id' => null,
            'status' => 'open',
        ]);

        DispenseItem::create([
            'dispense_order_id' => $openOrder->id,
            'medicine_id' => $medA->id,
            'medicine_name' => $medA->name,
            'quantity' => 1,
            'dispensed_quantity' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($doctor)->post(route('prescription.update', $consultation->id), [
            'diagnosis' => 'Test',
            'clinical_notes' => '',
            'investigations' => '',
            'advice' => '',
            'medicines' => [
                ['name' => $medA->name],
                ['name' => $medB->name],
            ],
        ]);
        $response->assertRedirect();

        $orders = DispenseOrder::where('pharmacy_store_id', $store->id)
            ->where('consultation_id', $consultation->id)
            ->get();

        $this->assertCount(1, $orders);

        $items = DispenseItem::where('dispense_order_id', $openOrder->id)->get();
        $this->assertCount(2, $items);
        $this->assertTrue($items->contains(fn (DispenseItem $i) => $i->medicine_name === $medB->name));
    }

    private function fakeUser(string $role): User
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
