<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AdminScraperController extends Controller
{
    public function index(Request $request)
    {
        $supported = ['1mg.com' => '1mg', 'pharmeasy.in' => 'pharmeasy', 'netmeds.com' => 'netmeds', 'apollopharmacy.in' => 'apollo'];
        $lastOutput = $request->query('output');
        $items = null;
        if ($lastOutput && file_exists(base_path($lastOutput))) {
            $data = json_decode(@file_get_contents(base_path($lastOutput)), true);
            $items = is_array($data) ? count($data) : null;
        }
        return view('admin.scraper', compact('supported', 'lastOutput', 'items'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string'],
            'urls' => ['nullable', 'string'],
        ]);

        $map = ['1mg.com' => '1mg', 'pharmeasy.in' => 'pharmeasy', 'netmeds.com' => 'netmeds', 'apollopharmacy.in' => 'apollo'];
        $domain = strtolower(trim($request->input('domain')));
        if (!isset($map[$domain])) {
            return back()->with('error', 'Unsupported domain.')->withInput();
        }
        $site = $map[$domain];

        $urlsText = trim((string) $request->input('urls', ''));
        $urls = array_values(array_filter(array_map(function ($l) {
            return trim($l);
        }, preg_split('/\r\n|\n|\r/', $urlsText))));

        if (empty($urls)) {
            if ($site === '1mg') {
                $urls = [
                    'https://www.1mg.com/drugs/dolo-650-tablet-74467',
                    'https://www.1mg.com/drugs/dolopar-650-tablet-329440',
                    'https://www.1mg.com/drugs/dolo-xtraa-tablet-1051266',
                ];
            }
        }

        if (empty($urls)) {
            return back()->with('error', 'Provide at least one product URL for this domain.')->withInput();
        }

        $dir = 'storage/catalog/' . $site;
        @mkdir(base_path($dir), 0777, true);
        $filename = $dir . '/' . $site . '_' . Str::slug($domain) . '_' . now()->format('Ymd_His') . '.json';

        $result = Artisan::call('pharmacy:scrape-brands', [
            '--site' => $site,
            '--urls' => $urls,
            '--output' => $filename,
        ]);

        return redirect()->route('admin.scraper.index', ['output' => $filename])->with('status', "Scrape finished. Output: $filename");
    }
}

