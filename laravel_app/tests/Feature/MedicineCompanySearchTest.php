<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Manufacturer;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MedicineCompanySearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_brand_picker_search_matches_manufacturer_name_with_same_composition(): void
    {
        $user = User::factory()->create();

        $cipla = Manufacturer::firstOrCreate(['name' => 'Cipla']);
        $other = Manufacturer::firstOrCreate(['name' => 'Other Pharma']);

        $paracetamol = Ingredient::firstOrCreate(['name' => 'Paracetamol']);
        $suffix = uniqid('test_', true);

        $m1 = Medicine::create([
            'name' => "A {$suffix} Paracetamol 500mg Tablet",
            'brand_name' => "A {$suffix} Dolo 500 Tablet",
            'manufacturer_id' => $cipla->id,
            'is_active' => true,
            'primary_ingredient' => 'Paracetamol',
        ]);
        $m1->ingredients()->attach($paracetamol->id, ['strength_value' => 500, 'strength_unit' => 'mg']);

        $m2 = Medicine::create([
            'name' => "A {$suffix} Paracetamol 650mg Tablet",
            'brand_name' => "A {$suffix} Dolo 650 Tablet",
            'manufacturer_id' => $cipla->id,
            'is_active' => true,
            'primary_ingredient' => 'Paracetamol',
        ]);
        $m2->ingredients()->attach($paracetamol->id, ['strength_value' => 650, 'strength_unit' => 'mg']);

        $m3 = Medicine::create([
            'name' => "A {$suffix} Ibuprofen 400mg Tablet",
            'brand_name' => "A {$suffix} Ibu 400",
            'manufacturer_id' => $cipla->id,
            'is_active' => true,
            'primary_ingredient' => 'Ibuprofen',
        ]);

        $m4 = Medicine::create([
            'name' => "A {$suffix} Paracetamol 500mg Tablet Other",
            'brand_name' => "A {$suffix} Para 500 Other",
            'manufacturer_id' => $other->id,
            'is_active' => true,
            'primary_ingredient' => 'Paracetamol',
        ]);
        $m4->ingredients()->attach($paracetamol->id, ['strength_value' => 500, 'strength_unit' => 'mg']);

        $resp = $this->actingAs($user)->getJson('/api/medicines?only_brands=1&page=1&per_page=200&search_by=brand&q=Cipla&composition=Paracetamol');
        $resp->assertOk();
        $items = $resp->json('items');
        $this->assertIsArray($items);

        $ids = array_map(fn ($it) => $it['id'] ?? null, $items);

        $this->assertContains($m1->id, $ids);
        $this->assertContains($m2->id, $ids);
        $this->assertNotContains($m3->id, $ids);
        $this->assertNotContains($m4->id, $ids);
    }

    public function test_brand_picker_search_matches_manufacturer_raw_when_no_linked_manufacturer(): void
    {
        $user = User::factory()->create();
        $suffix = uniqid('test_', true);
        $m = Medicine::create([
            'name' => "A {$suffix} Paracetamol 500mg Tablet",
            'brand_name' => "A {$suffix} Some Brand",
            'manufacturer_id' => null,
            'manufacturer_raw' => 'Cipla',
            'is_active' => true,
            'primary_ingredient' => 'Paracetamol',
        ]);

        $resp = $this->actingAs($user)->getJson('/api/medicines?only_brands=1&page=1&per_page=200&search_by=brand&q=Cipla&composition=Paracetamol');
        $resp->assertOk();

        $ids = array_map(fn ($it) => $it['id'] ?? null, (array) $resp->json('items'));
        $this->assertContains($m->id, $ids);
    }
}
