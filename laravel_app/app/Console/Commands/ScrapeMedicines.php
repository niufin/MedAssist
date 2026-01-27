<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeMedicines extends Command
{
    protected $signature = 'pharmacy:scrape-brands 
        {--site=1mg : Source site (1mg|pharmeasy|netmeds|apollo)} 
        {--urls=* : Product URLs to scrape} 
        {--output=storage/catalog/brands.json : Output JSON path relative to base_path()}';

    protected $description = 'Scrape brand/composition data from Indian pharmacy sites into normalized JSON';

    public function handle(): int
    {
        $site = strtolower((string) $this->option('site'));
        $urls = (array) $this->option('urls');
        $outputRel = (string) $this->option('output');
        $output = base_path($outputRel);

        if (empty($urls)) {
            $this->error('Provide one or more product URLs using --urls=');
            return 1;
        }

        @mkdir(dirname($output), 0777, true);

        $items = [];
        foreach ($urls as $url) {
            $this->info("Fetching: {$url}");
            try {
                $resp = Http::withOptions(['verify' => false])->withHeaders([
                    'User-Agent' => 'MedAssist-Scraper/1.0 (+https://doctor.niufin.cloud)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])->timeout(20)->get($url);
            } catch (\Throwable $e) {
                $this->warn("Request failed: {$e->getMessage()}");
                continue;
            }
            if (!$resp->ok()) {
                $this->warn("HTTP {$resp->status()}");
                continue;
            }
            $html = $resp->body();
            $parsed = $this->parse($site, $url, $html);
            if ($parsed) {
                $items[] = $parsed;
            } else {
                $this->warn("Parse failed for: {$url}");
            }
            usleep(600000); // 0.6s between requests
        }

        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($output, $json);
        $this->info("Saved: {$output}");
        $this->info("Items: " . count($items));
        return 0;
    }

    private function parse(string $site, string $url, string $html): ?array
    {
        switch ($site) {
            case '1mg':
                return $this->parse1mg($url, $html);
            case 'pharmeasy':
                return $this->parsePharmEasy($url, $html);
            case 'netmeds':
                return $this->parseNetmeds($url, $html);
            case 'apollo':
                return $this->parseApollo($url, $html);
            default:
                return null;
        }
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
        // Examples: "650 mg", "50mg", "500mg/5ml", "2% w/w"
        $text = trim(str_replace(['MG','ML','mcg','MCG','G','%','w/w','w/v'], ['mg','ml','mcg','mcg','g','%','w/w','w/v'], $text));
        // split by "/" keep first value
        $part = explode('/', $text)[0];
        if (preg_match('/([\d\.]+)\s*([a-z%]+)/i', $part, $m)) {
            $val = (float) $m[1];
            $unit = $this->normalizeUnit($m[2]);
            return [$val, $unit];
        }
        return [null, null];
    }

    private function parse1mg(string $url, string $html): ?array
    {
        // Strip scripts/styles to avoid CSS tokens
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\\1>/si', '', $html);
        // Brand name from <title> or H1
        $brand = null;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $brand = strip_tags($m[1]);
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $brand = html_entity_decode(strip_tags($m[1]));
            $brand = preg_replace('/\s*\|\s*1mg.*/', '', $brand);
        }
        $brand = $brand ? trim($brand) : null;

        // Dosage form heuristic
        $dosage = null;
        if ($brand) {
            if (stripos($brand, 'tablet') !== false) $dosage = 'tablet';
            elseif (stripos($brand, 'capsule') !== false) $dosage = 'capsule';
            elseif (stripos($brand, 'syrup') !== false) $dosage = 'syrup';
        }

        // SALT COMPOSITION section
        $composition = [];
        $scope = $html;
        if (preg_match('/SALT\\s+COMPOSITION(.*?)(<\\/section>|<\\/div>|$)/si', $html, $sec)) {
            $scope = $sec[1];
        }
        // Primary pattern: "(650mg)" style
        preg_match_all('/([A-Za-z][A-Za-z0-9 \\-]+?)\\s*\\(([\\d\\.]+)\\s*([a-z%]+)\\)/i', $scope, $matches1, PREG_SET_ORDER);
        foreach ($matches1 as $mm) {
            [$val, $unit] = $this->parseStrength($mm[2] . $mm[3]);
            $composition[] = [
                'ingredient' => trim($mm[1]),
                'strength_value' => $val,
                'strength_unit' => $unit,
            ];
        }
        // Secondary pattern within scope: "Paracetamol 650 mg"
        preg_match_all('/([A-Za-z][A-Za-z0-9 \\-]+?)\\s+([\\d\\.]+)\\s*(mg|mcg|g|ml|%)/i', $scope, $matches2, PREG_SET_ORDER);
        foreach ($matches2 as $mm) {
            [$val, $unit] = $this->parseStrength($mm[2] . $mm[3]);
            $item = [
                'ingredient' => trim($mm[1]),
                'strength_value' => $val,
                'strength_unit' => $unit,
            ];
            // Avoid duplicates
            $exists = false;
            foreach ($composition as $c) {
                if (strcasecmp($c['ingredient'], $item['ingredient']) === 0 && $c['strength_value'] === $item['strength_value'] && $c['strength_unit'] === $item['strength_unit']) {
                    $exists = true; break;
                }
            }
            if (!$exists) $composition[] = $item;
        }
        // Fallback: try number in brand
        if (empty($composition) && preg_match('/\\b(\\d{2,4})\\s*(mg|mcg|g)\\b/i', $brand ?? '', $sm)) {
            [$val, $unit] = $this->parseStrength($sm[1] . $sm[2]);
            $composition[] = [
                'ingredient' => 'Unknown',
                'strength_value' => $val,
                'strength_unit' => $unit,
            ];
        }

        if (!$brand) return null;

        return [
            'brand_name' => $brand,
            'dosage_form' => $dosage,
            'composition' => $composition,
            'generic_name' => null,
            'manufacturer' => null,
            'pack_size' => null,
            'mrp' => null,
            'schedule' => null,
            'rx_required' => null,
            'source_url' => $url,
            'source' => '1mg',
            'last_seen' => now()->toDateString(),
        ];
    }

    private function parsePharmEasy(string $url, string $html): ?array
    {
        $brand = null;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $brand = trim(strip_tags($m[1]));
        }
        $dosage = null;
        if ($brand) {
            if (stripos($brand, 'tablet') !== false) $dosage = 'tablet';
            elseif (stripos($brand, 'capsule') !== false) $dosage = 'capsule';
            elseif (stripos($brand, 'syrup') !== false) $dosage = 'syrup';
        }
        // composition from common pattern: "Composition: Paracetamol 650 mg"
        $composition = [];
        if (preg_match('/Composition[^<:]*:\s*(.*?)</si', $html, $m)) {
            $line = strip_tags($m[1]);
            // split by "+" for combos
            foreach (preg_split('/\s*\+\s*/', $line) as $seg) {
                if (preg_match('/([A-Za-z0-9 \-]+)\s+([\d\.]+)\s*([a-z%]+)/i', $seg, $mm)) {
                    [$val, $unit] = $this->parseStrength($mm[2].$mm[3]);
                    $composition[] = [
                        'ingredient' => trim($mm[1]),
                        'strength_value' => $val,
                        'strength_unit' => $unit,
                    ];
                }
            }
        }
        if (!$brand) return null;
        return [
            'brand_name' => $brand,
            'dosage_form' => $dosage,
            'composition' => $composition,
            'generic_name' => null,
            'manufacturer' => null,
            'pack_size' => null,
            'mrp' => null,
            'schedule' => null,
            'rx_required' => null,
            'source_url' => $url,
            'source' => 'PharmEasy',
            'last_seen' => now()->toDateString(),
        ];
    }

    private function parseNetmeds(string $url, string $html): ?array
    {
        $brand = null;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $brand = trim(strip_tags($m[1]));
        }
        $dosage = null;
        if ($brand) {
            if (stripos($brand, 'tablet') !== false) $dosage = 'tablet';
            elseif (stripos($brand, 'capsule') !== false) $dosage = 'capsule';
            elseif (stripos($brand, 'syrup') !== false) $dosage = 'syrup';
        }
        $composition = [];
        if (preg_match('/Ingredients[^<]*<\/[^>]+>\s*<[^>]+>(.*?)<\/div>/si', $html, $m)) {
            $line = strip_tags($m[1]);
            foreach (preg_split('/,|\+/', $line) as $seg) {
                if (preg_match('/([A-Za-z0-9 \-]+)\s+([\d\.]+)\s*([a-z%]+)/i', $seg, $mm)) {
                    [$val, $unit] = $this->parseStrength($mm[2].$mm[3]);
                    $composition[] = [
                        'ingredient' => trim($mm[1]),
                        'strength_value' => $val,
                        'strength_unit' => $unit,
                    ];
                } else {
                    $name = trim($seg);
                    if ($name !== '') {
                        $composition[] = ['ingredient' => $name, 'strength_value' => null, 'strength_unit' => null];
                    }
                }
            }
        }
        if (!$brand) return null;
        return [
            'brand_name' => $brand,
            'dosage_form' => $dosage,
            'composition' => $composition,
            'generic_name' => null,
            'manufacturer' => null,
            'pack_size' => null,
            'mrp' => null,
            'schedule' => null,
            'rx_required' => null,
            'source_url' => $url,
            'source' => 'Netmeds',
            'last_seen' => now()->toDateString(),
        ];
    }

    private function parseApollo(string $url, string $html): ?array
    {
        $brand = null;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $brand = trim(strip_tags($m[1]));
        }
        $dosage = null;
        if ($brand) {
            if (stripos($brand, 'tablet') !== false) $dosage = 'tablet';
            elseif (stripos($brand, 'capsule') !== false) $dosage = 'capsule';
            elseif (stripos($brand, 'syrup') !== false) $dosage = 'syrup';
        }
        $composition = [];
        if (preg_match('/Contains[^<]*:\s*(.*?)</si', $html, $m)) {
            $line = strip_tags($m[1]);
            foreach (preg_split('/,|\+/', $line) as $seg) {
                if (preg_match('/([A-Za-z0-9 \-]+)\s+([\d\.]+)\s*([a-z%]+)/i', $seg, $mm)) {
                    [$val, $unit] = $this->parseStrength($mm[2].$mm[3]);
                    $composition[] = [
                        'ingredient' => trim($mm[1]),
                        'strength_value' => $val,
                        'strength_unit' => $unit,
                    ];
                } else {
                    $name = trim($seg);
                    if ($name !== '') {
                        $composition[] = ['ingredient' => $name, 'strength_value' => null, 'strength_unit' => null];
                    }
                }
            }
        }
        if (!$brand) return null;
        return [
            'brand_name' => $brand,
            'dosage_form' => $dosage,
            'composition' => $composition,
            'generic_name' => null,
            'manufacturer' => null,
            'pack_size' => null,
            'mrp' => null,
            'schedule' => null,
            'rx_required' => null,
            'source_url' => $url,
            'source' => 'Apollo',
            'last_seen' => now()->toDateString(),
        ];
    }
}
