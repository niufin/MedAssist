<?php

namespace Tests\Feature;

use App\Http\Controllers\DoctorController;
use Tests\TestCase;

class PrescriptionExtractionTest extends TestCase
{
    public function test_extracts_prescription_json_from_tagged_block(): void
    {
        $ctrl = new DoctorController();
        $m = new \ReflectionMethod($ctrl, 'extractPrescriptionJson');
        $m->setAccessible(true);

        $reply = "Some text\n[PRESCRIPTION_START]{\"diagnosis\":\"X\",\"medicines\":[]}[PRESCRIPTION_END]\nMore text";
        $json = $m->invoke($ctrl, $reply, true);

        $this->assertIsArray($json);
        $this->assertSame('X', $json['diagnosis']);
        $this->assertSame([], $json['medicines']);
    }

    public function test_extracts_prescription_json_without_tags_when_expected(): void
    {
        $ctrl = new DoctorController();
        $m = new \ReflectionMethod($ctrl, 'extractPrescriptionJson');
        $m->setAccessible(true);

        $reply = "Final report...\n{\"diagnosis\":\"Y\",\"medicines\":[{\"name\":\"Drug\",\"dosage\":\"1\",\"frequency\":\"1-0-1\",\"duration\":\"5d\",\"instruction\":\"\"}]}\n";
        $json = $m->invoke($ctrl, $reply, true);

        $this->assertIsArray($json);
        $this->assertSame('Y', $json['diagnosis']);
        $this->assertCount(1, $json['medicines']);
    }
}

