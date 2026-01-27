<?php

namespace Tests\Feature;

use App\Console\Commands\ImportMedicinesCsv;
use Tests\TestCase;

class ImportMedicinesCsvTest extends TestCase
{
    public function test_parses_active_ingredients_and_strengths_without_db(): void
    {
        $cmd = app(ImportMedicinesCsv::class);

        $parseActive = new \ReflectionMethod($cmd, 'parseActiveIngredients');
        $parseActive->setAccessible(true);

        $parseStrength = new \ReflectionMethod($cmd, 'parseStrength');
        $parseStrength->setAccessible(true);

        $active = "[{'name': 'Amoxycillin', 'strength': '500mg', 'full_description': 'Amoxycillin  (500mg)'}, {'name': 'Clavulanic Acid', 'strength': '125mg', 'full_description': 'Clavulanic Acid (125mg)'}]";
        $components = $parseActive->invoke($cmd, $active);

        $this->assertCount(2, $components);
        $this->assertSame('Amoxycillin', $components[0]['ingredient']);
        $this->assertSame(500.0, $components[0]['strength_value']);
        $this->assertSame('mg', $components[0]['strength_unit']);

        [$val, $unit] = $parseStrength->invoke($cmd, '30mg/5ml');
        $this->assertSame(30.0, $val);
        $this->assertSame('mg', $unit);
    }
}
