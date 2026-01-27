<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Medicine;
use App\Models\Ingredient;
use App\Models\Manufacturer;
use App\Models\DosageForm;
use App\Models\Route as MedRoute;
use App\Models\Package;

class ImportMedicinesCsv extends Command
{
    protected $signature = 'pharmacy:import-csv {--path= : Absolute CSV path} {--output=storage/catalog/csv_import.json} {--anomalies=storage/catalog/anomalies.csv} {--no-db : Only convert CSV to JSON, skip database writes}';
    protected $description = 'Import medicines from a CSV file, create/update records and link compositions';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');
        $noDb = (bool) $this->option('no-db');
        $path = (string) $this->option('path');
        if ($path === '') {
            $this->error('Provide --path to the CSV file');
            return 1;
        }
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $outputRel = (string) $this->option('output');
        $output = base_path($outputRel);
        @mkdir(dirname($output), 0777, true);

        $created = 0;
        $updated = 0;
        $linked = 0;
        $errors = 0;
        $converted = 0;
        $ofh = fopen($output, 'w');
        if (!$ofh) {
            $this->error('Unable to open output file for writing');
            return 1;
        }
        fwrite($ofh, "[\n");
        $firstOut = true;
        $anomRel = (string) $this->option('anomalies');
        $anomPath = base_path($anomRel);
        @mkdir(dirname($anomPath), 0777, true);
        $afh = fopen($anomPath, 'w');
        if ($afh) {
            fwrite($afh, "line,source_product_id,brand_name,dosage_form,strength,issue,error\n");
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->error('Unable to open CSV');
            return 1;
        }

        $headers = fgetcsv($fh);
        if (!is_array($headers)) {
            $this->error('Invalid CSV header');
            return 1;
        }
        $headers = array_map(function ($h) { return $this->normalizeHeader((string) $h); }, $headers);

        $map = $this->buildHeaderMap($headers);

        DB::disableQueryLog();
        @set_time_limit(0);

        $manufacturerCache = [];
        $dosageFormCache = [];
        $routeCache = [];
        $ingredientCache = [];

        $i = 0;
        $lineNo = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $lineNo++;
            $rec = $this->rowToAssoc($headers, $row);
            try {
                $norm = $this->normalizeRow($rec, $map);
                if (!$norm['name']) continue;

                $genericDisplay = $this->buildGenericDisplay($norm['composition']);
                $genericDisplayData = [
                    'text' => $genericDisplay,
                    'components' => $norm['composition'],
                ];
                $medicine = null;
                $manuId = null;
                $dosageFormId = null;
                $routeId = null;

                if (!$noDb) {
                    $isDiscontinued = (bool) ($norm['is_discontinued'] ?? false);
                    if ($norm['manufacturer']) {
                        $mk = strtolower(trim((string) $norm['manufacturer']));
                        if (isset($manufacturerCache[$mk])) {
                            $manuId = $manufacturerCache[$mk];
                        } else {
                            $manu = Manufacturer::firstOrCreate(['name' => $norm['manufacturer']]);
                            $manuId = $manu->id;
                            $manufacturerCache[$mk] = $manuId;
                        }
                    }
                    if ($norm['dosage_form']) {
                        $dfName = $this->normalizeTitle($norm['dosage_form']);
                        $dk = strtolower($dfName);
                        if (isset($dosageFormCache[$dk])) {
                            $dosageFormId = $dosageFormCache[$dk];
                        } else {
                            $df = DosageForm::firstOrCreate(['name' => $dfName]);
                            $dosageFormId = $df->id;
                            $dosageFormCache[$dk] = $dosageFormId;
                        }
                    }
                    if (!empty($norm['route'])) {
                        $rName = $this->normalizeTitle($norm['route']);
                        $rk = strtolower($rName);
                        if (isset($routeCache[$rk])) {
                            $routeId = $routeCache[$rk];
                        } else {
                            $r = MedRoute::firstOrCreate(['name' => $rName]);
                            $routeId = $r->id;
                            $routeCache[$rk] = $routeId;
                        }
                    }

                    $data = [
                        'name' => $norm['name'],
                        'brand_name' => $norm['name'],
                        'strength' => $norm['strength'],
                        'type' => $norm['dosage_form'],
                        'therapeutic_class' => $norm['therapeutic_class'],
                        'schedule' => $norm['schedule'],
                        'rx_required' => (bool) ($norm['rx_required'] ?? false),
                        'generic_display' => $genericDisplayData,
                        'manufacturer_id' => $manuId,
                        'manufacturer_raw' => $norm['manufacturer_raw'],
                        'dosage_form_id' => $dosageFormId,
                        'route_id' => $routeId,
                        'source_product_id' => $norm['source_product_id'],
                        'source_name' => $norm['source_name'],
                        'is_discontinued' => $isDiscontinued,
                        'num_active_ingredients' => $norm['num_active_ingredients'],
                        'primary_ingredient' => $norm['primary_ingredient'],
                        'primary_strength' => $norm['primary_strength'],
                        'is_active' => !$isDiscontinued,
                    ];
                    if (!$data['source_product_id'] && ($data['strength'] === null || $data['strength'] === '')) {
                        $errors++;
                        if ($afh) {
                            $safeName = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $data['name']);
                            $safeDf = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $data['type']);
                            fwrite($afh, "{$lineNo},,{$safeName},{$safeDf},,missing_strength_and_source_id,\n");
                        }
                        continue;
                    }

                    $existing = null;
                    if ($data['source_product_id']) {
                        $existing = Medicine::where('source_product_id', $data['source_product_id'])->first();
                    }
                    if (!$existing) {
                        $existing = Medicine::where('name', $data['name'])
                            ->where('strength', $data['strength'])
                            ->first();
                    }

                    if ($existing) {
                        $existing->fill($data);
                        if ($existing->isDirty()) {
                            $existing->save();
                            $updated++;
                        }
                        $medicine = $existing;
                    } else {
                        try {
                            $medicine = Medicine::create($data);
                            $created++;
                        } catch (QueryException $qe) {
                            $fallback = Medicine::where('name', $data['name'])
                                ->where('strength', $data['strength'])
                                ->first();
                            if ($fallback) {
                                $fallback->fill($data);
                                if ($fallback->isDirty()) {
                                    $fallback->save();
                                    $updated++;
                                }
                                $medicine = $fallback;
                            } else {
                                throw $qe;
                            }
                        }
                    }

                    foreach ($norm['composition'] as $comp) {
                        $iname = $comp['ingredient'];
                        if (!$iname) continue;
                        $ik = strtolower(trim((string) $iname));
                        if (isset($ingredientCache[$ik])) {
                            $ingId = $ingredientCache[$ik];
                        } else {
                            $ing = Ingredient::firstOrCreate(['name' => $iname], ['synonyms' => null, 'atc_code' => null]);
                            $ingId = $ing->id;
                            $ingredientCache[$ik] = $ingId;
                        }
                        $existsPivot = $medicine->ingredients()
                            ->where('ingredients.id', $ingId)
                            ->wherePivot('strength_value', $comp['strength_value'])
                            ->wherePivot('strength_unit', $comp['strength_unit'])
                            ->exists();
                        if (!$existsPivot) {
                            $medicine->ingredients()->attach($ingId, [
                                'strength_value' => $comp['strength_value'],
                                'strength_unit' => $comp['strength_unit'],
                            ]);
                            $linked++;
                        }
                    }

                    $packLabel = $norm['packaging_raw'];
                    if (!$packLabel && $norm['pack_unit'] && $norm['pack_size']) {
                        $packLabel = $norm['pack_unit'] . ' of ' . $norm['pack_size'] . ' ' . ($norm['dosage_form'] ?: '');
                    }
                    if (!$packLabel) {
                        $packLabel = $norm['pack_size'];
                    }
                    $mrpValue = $norm['mrp'] !== null ? $norm['mrp'] : $norm['price_inr'];
                    if ($packLabel || $mrpValue !== null || $norm['price_inr'] !== null) {
                        [$psv, $psu, $ptype] = $this->parsePackSize($packLabel);
                        $existsPack = Package::where('medicine_id', $medicine->id)
                            ->where('pack_size_value', $psv)
                            ->where('pack_size_unit', $psu)
                            ->where('pack_type', $ptype)
                            ->when($mrpValue === null, function ($q) {
                                $q->whereNull('mrp');
                            }, function ($q) use ($mrpValue) {
                                $q->where('mrp', $mrpValue);
                            })
                            ->when($norm['price_inr'] === null, function ($q) {
                                $q->whereNull('price_inr');
                            }, function ($q) use ($norm) {
                                $q->where('price_inr', $norm['price_inr']);
                            })
                            ->exists();
                        if (!$existsPack) {
                            Package::create([
                                'medicine_id' => $medicine->id,
                                'pack_size_value' => $psv,
                                'pack_size_unit' => $psu,
                                'pack_type' => $ptype,
                                'mrp' => $mrpValue,
                                'price_inr' => $norm['price_inr'],
                                'hsn_code' => null,
                                'barcode' => null,
                                'packaging_raw' => $norm['packaging_raw'],
                            ]);
                        }
                    }
                }
                
                if ($afh) {
                    if (!$norm['strength']) {
                        $safeName = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $norm['name']);
                        $safeDf = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $norm['dosage_form']);
                        $pid = $norm['source_product_id'] !== null ? (string) $norm['source_product_id'] : '';
                        fwrite($afh, "{$lineNo},{$pid},{$safeName},{$safeDf},,missing_strength,\n");
                    }
                    if (empty($norm['composition'])) {
                        $safeName = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $norm['name']);
                        $safeDf = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $norm['dosage_form']);
                        $safeStrength = str_replace(["\n", "\r", ","], [" ", " ", " "], (string) $norm['strength']);
                        $pid = $norm['source_product_id'] !== null ? (string) $norm['source_product_id'] : '';
                        fwrite($afh, "{$lineNo},{$pid},{$safeName},{$safeDf},{$safeStrength},missing_composition,\n");
                    }
                }

                $outRow = [
                    'product_id' => $norm['source_product_id'],
                    'brand_name' => $norm['name'],
                    'strength' => $norm['strength'],
                    'dosage_form' => $norm['dosage_form'],
                    'therapeutic_class' => $norm['therapeutic_class'],
                    'composition' => $norm['composition'],
                    'generic_name' => $norm['generic_name'],
                    'manufacturer' => $norm['manufacturer'],
                    'manufacturer_raw' => $norm['manufacturer_raw'],
                    'pack_size' => $norm['pack_size'],
                    'pack_unit' => $norm['pack_unit'],
                    'packaging_raw' => $norm['packaging_raw'],
                    'mrp' => $norm['mrp'],
                    'price_inr' => $norm['price_inr'],
                    'is_discontinued' => $norm['is_discontinued'],
                    'num_active_ingredients' => $norm['num_active_ingredients'],
                    'primary_ingredient' => $norm['primary_ingredient'],
                    'primary_strength' => $norm['primary_strength'],
                    'route' => $norm['route'],
                    'schedule' => $norm['schedule'],
                    'rx_required' => $norm['rx_required'],
                    'source' => $norm['source_name'] ?: 'csv',
                    'last_seen' => now()->toDateString(),
                ];
                $json = json_encode($outRow, JSON_UNESCAPED_SLASHES);
                if (!$firstOut) {
                    fwrite($ofh, ",\n");
                }
                fwrite($ofh, "  " . $json);
                $firstOut = false;
                $converted++;
                $i++;
                if (($i % 10000) === 0) {
                    if ($noDb) {
                        $this->info("Processed {$i} rows (convert only)...");
                    } else {
                        $this->info("Processed {$i} rows... created={$created}, updated={$updated}, linked={$linked}, errors={$errors}");
                    }
                }
                if (($i % 500) === 0) {
                    gc_collect_cycles();
                }
            } catch (\Throwable $e) {
                $errors++;
                $pid = isset($norm) && isset($norm['source_product_id']) && $norm['source_product_id'] !== null ? (string) $norm['source_product_id'] : '';
                $name = isset($norm) && isset($norm['name']) ? (string) $norm['name'] : '';
                $df = isset($norm) && isset($norm['dosage_form']) ? (string) $norm['dosage_form'] : '';
                $strength = isset($norm) && isset($norm['strength']) ? (string) $norm['strength'] : '';
                $safeName = str_replace(["\n", "\r", ","], [" ", " ", " "], $name);
                $safeDf = str_replace(["\n", "\r", ","], [" ", " ", " "], $df);
                $safeStrength = str_replace(["\n", "\r", ","], [" ", " ", " "], $strength);
                $safeErr = str_replace(["\n", "\r"], [" ", " "], $e->getMessage());
                if ($afh) {
                    fwrite($afh, "{$lineNo},{$pid},{$safeName},{$safeDf},{$safeStrength},row_exception,{$safeErr}\n");
                }
                Log::warning('pharmacy.import_csv.row_failed', [
                    'line' => $lineNo,
                    'source_product_id' => $pid ?: null,
                    'name' => $name ?: null,
                    'dosage_form' => $df ?: null,
                    'strength' => $strength ?: null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        fclose($fh);

        fwrite($ofh, "\n]\n");
        fclose($ofh);
        if ($afh) fclose($afh);

        if ($noDb) {
            $this->info("Converted rows: {$converted}, errors={$errors}");
        } else {
            $this->info("Imported: created={$created}, updated={$updated}, compositions_linked={$linked}, errors={$errors}");
        }
        $this->info("Output JSON: {$output}");
        if (file_exists($anomPath)) {
            $this->info("Anomalies CSV: {$anomPath}");
        }
        return 0;
    }

    private function buildHeaderMap(array $headers): array
    {
        $map = [
            'source_product_id' => ['product_id'],
            'source_name' => ['source_name','source'],
            'name' => ['brand_name','brand','product_name','name','brand'],
            'generic_name' => ['generic','generic_name','salt','ingredients','generic_drug_name'],
            'composition' => ['composition','ingredients','salt_composition','composition_details'],
            'active_ingredients' => ['active_ingredients','active_ingredient','actives','active_salts'],
            'dosage_form' => ['dosage_form','form','type','dosage','dosage_type'],
            'strength' => ['strength','dose_strength','strength_value'],
            'therapeutic_class' => ['class','therapeutic_class','category','therapeutic_category'],
            'manufacturer' => ['manufacturer','brand_owner','company','manufacturer_name'],
            'manufacturer_raw' => ['manufacturer_raw'],
            'price_inr' => ['price_inr'],
            'is_discontinued' => ['is_discontinued','discontinued'],
            'pack_size' => ['pack','pack_size','size','packaging_size','pack_size_label'],
            'pack_unit' => ['pack_unit'],
            'packaging_raw' => ['packaging_raw'],
            'mrp' => ['mrp','price','maximum_retail_price'],
            'schedule' => ['schedule','drug_schedule','schedule_category'],
            'rx_required' => ['rx_required','prescription_required','requires_prescription'],
            'route' => ['route','administration_route'],
            'num_active_ingredients' => ['num_active_ingredients'],
            'primary_ingredient' => ['primary_ingredient'],
            'primary_strength' => ['primary_strength'],
        ];
        $resolved = [];
        foreach ($map as $key => $alts) {
            $resolved[$key] = null;
            foreach ($alts as $a) {
                $normA = $this->normalizeHeader($a);
                $idx = array_search($normA, $headers, true);
                if ($idx !== false) { $resolved[$key] = $a; break; }
            }
        }
        return $resolved;
    }

    private function rowToAssoc(array $headers, array $row): array
    {
        $assoc = [];
        foreach ($headers as $i => $h) {
            $assoc[$h] = isset($row[$i]) ? $row[$i] : null;
        }
        return $assoc;
    }

    private function normalizeRow(array $rec, array $map): array
    {
        $sourceProductId = $this->val($rec, $map['source_product_id']);
        $sourceName = $this->val($rec, $map['source_name']);
        $name = $this->val($rec, $map['name']);
        $dosage = $this->val($rec, $map['dosage_form']);
        $strength = $this->val($rec, $map['strength']);
        $class = $this->val($rec, $map['therapeutic_class']);
        $gen = $this->val($rec, $map['generic_name']);
        $active = $this->val($rec, $map['active_ingredients']);
        $manu = $this->val($rec, $map['manufacturer']);
        $manuRaw = $this->val($rec, $map['manufacturer_raw']);
        $pack = $this->val($rec, $map['pack_size']);
        $packUnit = $this->val($rec, $map['pack_unit']);
        $packagingRaw = $this->val($rec, $map['packaging_raw']);
        $priceInr = $this->val($rec, $map['price_inr']);
        $isDiscontinued = $this->val($rec, $map['is_discontinued']);
        $mrp = $this->val($rec, $map['mrp']);
        $sched = $this->val($rec, $map['schedule']);
        $rx = $this->val($rec, $map['rx_required']);
        $route = $this->val($rec, $map['route']);
        $compText = $this->val($rec, $map['composition']);
        $numActives = $this->val($rec, $map['num_active_ingredients']);
        $primaryIngredient = $this->val($rec, $map['primary_ingredient']);
        $primaryStrength = $this->val($rec, $map['primary_strength']);

        $composition = [];
        if ($active) {
            $composition = $this->parseActiveIngredients($active);
        }
        if (empty($composition)) {
            $composition = $this->parseComposition($compText ?: $gen);
        }

        return [
            'source_product_id' => $sourceProductId !== null && $sourceProductId !== '' ? (int) $sourceProductId : null,
            'source_name' => $sourceName ? trim($sourceName) : 'csv_india',
            'name' => $name ? trim($name) : null,
            'dosage_form' => $dosage ? trim($dosage) : null,
            'strength' => $strength ? trim($strength) : null,
            'therapeutic_class' => $class ? trim($class) : null,
            'generic_name' => $gen ? trim($gen) : null,
            'manufacturer' => $manu ? trim($manu) : null,
            'manufacturer_raw' => $manuRaw ? trim($manuRaw) : null,
            'pack_size' => $pack ? trim($pack) : null,
            'mrp' => $mrp !== null && $mrp !== '' ? (float) preg_replace('/[^\d\.]/', '', (string) $mrp) : null,
            'price_inr' => $priceInr !== null && $priceInr !== '' ? (float) preg_replace('/[^\d\.]/', '', (string) $priceInr) : null,
            'schedule' => $sched ? trim($sched) : null,
            'rx_required' => $rx ? in_array(strtolower(trim($rx)), ['yes','true','1']) : null,
            'route' => $route ? trim($route) : null,
            'pack_unit' => $packUnit ? trim($packUnit) : null,
            'packaging_raw' => $packagingRaw ? trim($packagingRaw) : null,
            'is_discontinued' => $isDiscontinued !== null && $isDiscontinued !== '' ? in_array(strtolower(trim($isDiscontinued)), ['yes','true','1']) : null,
            'num_active_ingredients' => $numActives !== null && $numActives !== '' ? (int) $numActives : null,
            'primary_ingredient' => $primaryIngredient ? trim($primaryIngredient) : null,
            'primary_strength' => $primaryStrength ? trim($primaryStrength) : null,
            'composition' => $composition,
        ];
    }

    private function val(array $rec, ?string $key): ?string
    {
        if ($key === null) return null;
        $nk = $this->normalizeHeader($key);
        return isset($rec[$nk]) ? (string) $rec[$nk] : null;
    }

    private function normalizeUnit(?string $unit): ?string
    {
        if ($unit === null) return null;
        $u = strtolower(trim($unit));
        $map = ['milligram' => 'mg', 'microgram' => 'mcg', 'gram' => 'g', 'millilitre' => 'ml', 'milliliter' => 'ml'];
        return $map[$u] ?? $u;
    }

    private function parseStrength(string $text): array
    {
        $text = trim(str_replace(['MG','ML','mcg','MCG','G','%','w/w','w/v'], ['mg','ml','mcg','mcg','g','%','w/w','w/v'], $text));
        $part = explode('/', $text)[0];
        if (preg_match('/([\d\.]+)\s*([a-z%]+)/i', $part, $m)) {
            $val = (float) $m[1];
            $unit = $this->normalizeUnit($m[2]);
            return [$val, $unit];
        }
        return [null, null];
    }

    private function parseComposition(?string $text): array
    {
        $out = [];
        if (!$text) return $out;
        $chunks = preg_split('/\s*\+\s*|,\s*/', $text);
        foreach ($chunks as $seg) {
            $seg = trim($seg);
            if ($seg === '') continue;
            if (preg_match('/^([A-Za-z0-9 \-]+)\s+([\d\.]+)\s*([a-z%]+)/i', $seg, $mm)) {
                [$val, $unit] = $this->parseStrength($mm[2].$mm[3]);
                $out[] = [
                    'ingredient' => trim($mm[1]),
                    'strength_value' => $val,
                    'strength_unit' => $unit,
                ];
            } else {
                $out[] = ['ingredient' => $seg, 'strength_value' => null, 'strength_unit' => null];
            }
        }
        return $out;
    }

    private function parseActiveIngredients(string $text): array
    {
        $out = [];
        $text = trim($text);
        if ($text === '') return $out;
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            // Convert single-quoted python-like list to JSON
            $jsonish = preg_replace("/'([^']*)'/", '"$1"', $text);
            $decoded = json_decode($jsonish, true);
        }
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (!is_array($item)) continue;
                $name = isset($item['name']) ? trim((string) $item['name']) : null;
                $strength = isset($item['strength']) ? trim((string) $item['strength']) : null;
                if (!$name) continue;
                [$val, $unit] = $strength ? $this->parseStrength($strength) : [null, null];
                $out[] = [
                    'ingredient' => $name,
                    'strength_value' => $val,
                    'strength_unit' => $unit,
                ];
            }
        }
        return $out;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/[^a-z0-9]+/i', '_', $h);
        $h = preg_replace('/_+/', '_', $h);
        return trim($h, '_');
    }

    private function parsePackSize(?string $text): array
    {
        if (!$text) return [null, null, null];
        $t = strtolower(trim($text));
        $t = str_replace(['tabs','tab','tablet','tablets'], 'tablet', $t);
        $t = str_replace(['caps','cap','capsule','capsules'], 'capsule', $t);
        $t = str_replace(['mls','millilitre','milliliter'], 'ml', $t);
        $ptype = null;
        if (str_contains($t, 'strip')) $ptype = 'strip';
        if (str_contains($t, 'bottle')) $ptype = $ptype ?: 'bottle';
        if (str_contains($t, 'sachet')) $ptype = $ptype ?: 'sachet';
        if (str_contains($t, 'vial')) $ptype = $ptype ?: 'vial';
        if (preg_match('/([\d\.]+)\s*([a-z]+)/i', $t, $m)) {
            $val = (float) $m[1];
            $unit = $this->normalizeUnit($m[2]);
            return [$val, $unit, $ptype];
        }
        return [null, null, $ptype];
    }

    private function normalizeTitle(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);
        return ucfirst(strtolower($s));
    }

    private function buildGenericDisplay(array $composition): ?string
    {
        if (empty($composition)) return null;
        $parts = [];
        foreach ($composition as $c) {
            $name = $c['ingredient'] ?? null;
            $val = $c['strength_value'] ?? null;
            $unit = $c['strength_unit'] ?? null;
            if ($name && $val && $unit) {
                $parts[] = "{$name} {$val} {$unit}";
            } elseif ($name) {
                $parts[] = $name;
            }
        }
        return implode(' + ', $parts);
    }
}
