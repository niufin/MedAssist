<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSystemController extends Controller
{
    public function index()
    {
        return view('admin.system.index');
    }

    public function reloadAiMemory()
    {
        $aiServiceUrl = rtrim(config('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
        try {
            $response = Http::post("{$aiServiceUrl}/admin/reload-memory");
            if ($response->successful()) {
                return back()->with('status', 'AI Memory Reload Triggered Successfully.');
            }
            return back()->with('error', 'Failed to trigger reload: ' . $response->body());
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
        }
    }

    public function restartAiService()
    {
        $aiServiceUrl = rtrim(config('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
        $taskError = $this->runAiServiceScheduledTask();
        if ($taskError === null) {
            return back()->with('status', 'AI Service restart triggered on server. Please wait 10-20 seconds.');
        }

        try {
            $response = Http::timeout(5)->post("{$aiServiceUrl}/admin/restart");
            if ($response->successful()) {
                return back()->with('status', 'AI Service restart triggered. Please wait 10-20 seconds.');
            }
        } catch (\Throwable $e) {
            Log::warning('ai.restart.http_failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $fallbackError = $this->startAiServiceProcess();
        if ($fallbackError === null) {
            return back()->with('status', 'AI Service restart triggered on server. Please wait 10-20 seconds.');
        }

        Log::error('ai.restart.fallback_failed', [
            'fallback_error' => $fallbackError,
        ]);
        return back()->with('error', 'Failed to restart AI service: ' . $fallbackError);
    }

    protected function runAiServiceScheduledTask()
    {
        if (stripos(PHP_OS, 'WIN') !== 0) {
            return 'Scheduled task restart is only supported on Windows.';
        }

        $taskName = 'MedAssistService';
        $cmd = 'schtasks /Run /TN ' . escapeshellarg($taskName);

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        try {
            $proc = proc_open($cmd, $descriptor, $pipes);
            if (!is_resource($proc)) {
                return 'Failed to start scheduled task process.';
            }
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($proc);

            if ($exit === 0) {
                return null;
            }

            return trim($stderr ?: $stdout) ?: ('schtasks exit code ' . $exit);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function startAiServiceProcess()
    {
        $scriptPath = base_path('../python_service/scripts/start-ai.ps1');
        if (!file_exists($scriptPath)) {
            return 'AI start script not found.';
        }

        $aiServiceUrl = rtrim(config('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
        $host = parse_url($aiServiceUrl, PHP_URL_HOST) ?: '127.0.0.1';
        $port = parse_url($aiServiceUrl, PHP_URL_PORT) ?: 8001;

        $script = escapeshellarg($scriptPath);
        $hostArg = escapeshellarg($host);
        $portArg = (int) $port;

        $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -File {$script} -AIHost {$hostArg} -AIPort {$portArg}";

        try {
            if (stripos(PHP_OS, 'WIN') === 0) {
                $full = $cmd;
                $descriptor = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
                $proc = proc_open($full, $descriptor, $pipes);
                if (!is_resource($proc)) {
                    return 'Failed to start AI service process.';
                }
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($proc);
                if ($exit === 0) {
                    return null;
                }
                return trim($stderr ?: $stdout) ?: ('start-ai exit code ' . $exit);
            }

            if (function_exists('popen')) {
                @popen($cmd . ' > /dev/null 2>&1 &', 'r');
                return null;
            }

            @shell_exec($cmd . ' > /dev/null 2>&1 &');
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
