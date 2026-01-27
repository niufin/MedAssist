<?php

namespace Tests\Feature;

use App\Http\Controllers\DoctorController;
use App\Models\AiPrescriptionCache;
use App\Models\Consultation;
use App\Models\LabReport;
use App\Models\Medicine;
use App\Models\User;
use App\Services\Pharmacy\PrescriptionDispenseSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiDeterminismTest extends TestCase
{
    use DatabaseTransactions;

    public function test_final_prescription_uses_cache_for_same_age_gender_symptoms(): void
    {
        Medicine::create(['name' => 'Paracetamol', 'is_active' => true]);

        $c = Consultation::create([
            'symptoms' => 'Fever with body ache',
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'consulting',
        ]);

        $this->instance(PrescriptionDispenseSyncService::class, new class {
            public function syncForConsultation($c) {}
        });

        $aiResponse = implode("\n", [
            "Some analysis",
            "[PRESCRIPTION_START]",
            json_encode([
                'diagnosis' => 'Viral fever',
                'clinical_notes' => "C/O: Fever\nO/E: Stable",
                'investigations' => '',
                'medicines' => [
                    [
                        'name' => 'Tab. Paracetamol',
                        'dosage' => '500mg',
                        'frequency' => '1-0-1',
                        'duration' => '5 days',
                        'instruction' => 'After food',
                    ],
                ],
                'advice' => 'Hydration',
            ]),
            "[PRESCRIPTION_END]",
        ]);

        Http::fake([
            '*' => Http::response([
                'response' => $aiResponse,
                'sources' => [],
                'model' => 'openai/gpt-4o-mini',
            ], 200),
        ]);

        $controller = app(DoctorController::class);
        $rm = new \ReflectionMethod($controller, 'runAiDiagnosis');
        $rm->setAccessible(true);
        $rm->invoke($controller, $c, [], 'ignored', 'final', true);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('ai_prescription_caches', 1);

        $cacheRow = AiPrescriptionCache::query()->first();
        $this->assertNotNull($cacheRow);
        $this->assertIsArray($cacheRow->prescription_data);

        Http::fake(function () {
            $this->fail('AI HTTP endpoint should not be called when cache is present.');
        });

        $c2 = Consultation::create([
            'symptoms' => 'Fever with body ache',
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'consulting',
        ]);

        $rm->invoke($controller, $c2, [], 'ignored', 'final', true);
    }

    public function test_hallucinated_medicine_names_are_removed(): void
    {
        Medicine::create(['name' => 'Paracetamol', 'is_active' => true]);

        $c = Consultation::create([
            'symptoms' => 'Fever',
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'consulting',
        ]);

        $controller = app(DoctorController::class);
        $rm = new \ReflectionMethod($controller, 'validateAndFixPrescription');
        $rm->setAccessible(true);

        $rx = [
            'diagnosis' => 'x',
            'clinical_notes' => "C/O: Fever\nO/E: Stable",
            'investigations' => '',
            'advice' => '',
            'medicines' => [
                ['name' => 'Tab. Paracetamol', 'dosage' => '500mg', 'frequency' => '1-0-1', 'duration' => '3 days', 'instruction' => 'After food'],
                ['name' => 'Tab. TotallyMadeUpDrug', 'dosage' => '10mg', 'frequency' => '1-0-0', 'duration' => '3 days', 'instruction' => 'After food'],
            ],
        ];

        $fixed = $rm->invoke($controller, $rx, $c);
        $this->assertIsArray($fixed);
        $this->assertIsArray($fixed['medicines']);
        $names = array_map(fn ($m) => $m['name'] ?? '', $fixed['medicines']);

        $this->assertContains('Tab. Paracetamol', $names);
        $this->assertNotContains('Tab. TotallyMadeUpDrug', $names);
    }

    public function test_different_lab_reports_do_not_reuse_same_cache_signature(): void
    {
        Medicine::create(['name' => 'Paracetamol', 'is_active' => true]);

        $this->instance(PrescriptionDispenseSyncService::class, new class {
            public function syncForConsultation($c) {}
        });

        $uploader = User::factory()->create();

        $aiResponse = implode("\n", [
            "Some analysis",
            "[PRESCRIPTION_START]",
            json_encode([
                'diagnosis' => 'Viral fever',
                'clinical_notes' => "C/O: Fever\nO/E: Stable",
                'investigations' => '',
                'medicines' => [
                    [
                        'name' => 'Tab. Paracetamol',
                        'dosage' => '500mg',
                        'frequency' => '1-0-1',
                        'duration' => '5 days',
                        'instruction' => 'After food',
                    ],
                ],
                'advice' => 'Hydration',
            ]),
            "[PRESCRIPTION_END]",
        ]);

        $sent = 0;
        Http::fake(function () use (&$sent, $aiResponse) {
            $sent++;
            return Http::response([
                'response' => $aiResponse,
                'sources' => [],
                'model' => 'openai/gpt-4o-mini',
            ], 200);
        });

        $controller = app(DoctorController::class);
        $rm = new \ReflectionMethod($controller, 'runAiDiagnosis');
        $rm->setAccessible(true);

        $c1 = Consultation::create([
            'symptoms' => 'Fever with body ache',
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'consulting',
        ]);
        LabReport::create([
            'consultation_id' => $c1->id,
            'file_path' => 'lab1.pdf',
            'notes' => 'Hb 8.0',
            'uploaded_by' => $uploader->id,
        ]);
        $rm->invoke($controller, $c1, [], 'ignored', 'final', true);

        $c2 = Consultation::create([
            'symptoms' => 'Fever with body ache',
            'patient_age' => '34',
            'patient_gender' => 'Male',
            'status' => 'consulting',
        ]);
        LabReport::create([
            'consultation_id' => $c2->id,
            'file_path' => 'lab1.pdf',
            'notes' => 'Hb 12.0',
            'uploaded_by' => $uploader->id,
        ]);
        $rm->invoke($controller, $c2, [], 'ignored', 'final', true);

        $this->assertSame(2, $sent);
        $this->assertDatabaseCount('ai_prescription_caches', 2);
    }
}
