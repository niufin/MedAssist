<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Medicine;
use App\Models\Ingredient;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pharmacy:import-medicines {--source=extended}', function () {
    $source = (string) $this->option('source');

    $extendedPath = base_path('../python_service/data/medicines_extended.json');
    $basePath = base_path('../python_service/data/medicines_nlem.json');

    $path = $source === 'nlem' ? $basePath : $extendedPath;
    if (!file_exists($path)) {
        $path = file_exists($basePath) ? $basePath : $extendedPath;
    }

    if (!file_exists($path)) {
        $this->error('No medicines JSON file found.');
        return 1;
    }

    $raw = file_get_contents($path);
    $items = json_decode($raw, true);
    if (!is_array($items)) {
        $this->error('Invalid JSON medicines file.');
        return 1;
    }

    $created = 0;
    $updated = 0;
    $compositionsLinked = 0;

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['name'])) {
            continue;
        }

        $name = trim((string) $item['name']);
        $strength = isset($item['strength']) ? trim((string) $item['strength']) : null;

        $data = [
            'name' => $name,
            'strength' => $strength !== '' ? $strength : null,
            'type' => isset($item['type']) ? trim((string) $item['type']) : null,
            'therapeutic_class' => isset($item['class']) ? trim((string) $item['class']) : null,
            'is_active' => true,
        ];

        $existing = Medicine::where('name', $data['name'])
            ->where('strength', $data['strength'])
            ->first();

        if ($existing) {
            $existing->fill($data);
            if ($existing->isDirty()) {
                $existing->save();
                $updated++;
            }
        } else {
            Medicine::create($data);
            $created++;
        }

        $medicine = Medicine::where('name', $data['name'])
            ->where('strength', $data['strength'])
            ->first();

        if ($medicine) {
            $components = [];
            if (isset($item['ingredients']) && is_array($item['ingredients'])) {
                $components = $item['ingredients'];
            } elseif (isset($item['composition']) && is_array($item['composition'])) {
                $components = $item['composition'];
            }

            foreach ($components as $comp) {
                if (!is_array($comp) || empty($comp['name'])) {
                    continue;
                }
                $iname = trim((string) $comp['name']);
                if ($iname === '') continue;
                $ival = isset($comp['strength_value']) ? (float) $comp['strength_value'] : null;
                $iunit = isset($comp['strength_unit']) ? trim((string) $comp['strength_unit']) : null;

                $ing = Ingredient::firstOrCreate(['name' => $iname], [
                    'synonyms' => null,
                    'atc_code' => null,
                ]);

                $existsPivot = $medicine->ingredients()
                    ->where('ingredients.id', $ing->id)
                    ->wherePivot('strength_value', $ival)
                    ->wherePivot('strength_unit', $iunit)
                    ->exists();

                if (!$existsPivot) {
                    $medicine->ingredients()->attach($ing->id, [
                        'strength_value' => $ival,
                        'strength_unit' => $iunit,
                    ]);
                    $compositionsLinked++;
                }
            }
        }
    }

    $this->info("Imported medicines from: {$path}");
    $this->info("Created: {$created}, Updated: {$updated}");
    $this->info("Compositions linked: {$compositionsLinked}");
    return 0;
})->purpose('Import medicines into the pharmacy catalog');
