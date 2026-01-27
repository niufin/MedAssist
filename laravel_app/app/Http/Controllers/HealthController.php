<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function status(Request $request)
    {
        $db = 'down';
        try {
            DB::connection()->getPdo();
            $db = 'up';
        } catch (\Throwable $e) {
            $db = 'down';
        }

        $storage = is_writable(storage_path('app')) ? 'up' : 'down';

        $ai = 'down';
        $aiBackend = null;
        $aiChunks = null;
        $indexingState = null;
        try {
            $aiUrl = config('services.ai_service.url');
            $res = Http::timeout(5)->get($aiUrl . '/health');
            $ai = $res->successful() ? 'up' : 'down';
            try {
                $statusRes = Http::timeout(5)->get($aiUrl . '/status');
                if ($statusRes->successful()) {
                    $statusData = $statusRes->json();
                    $aiBackend = $statusData['backend'] ?? null;
                    $aiChunks = $statusData['doc_chunks'] ?? null;
                    $indexingState = $statusData['indexing_state'] ?? null;
                }
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Health Check Failed: ' . $e->getMessage());
            $ai = 'down';
        }

        return response()->json([
            'db' => $db,
            'storage' => $storage,
            'ai_service' => $ai,
            'ai_backend' => $aiBackend,
            'ai_doc_chunks' => $aiChunks,
            'ai_indexing_state' => $indexingState,
        ]);
    }
}
