param(
  [string]$AIHost = "127.0.0.1",
  [int]$AIPort = 8001
)
Set-Location $PSScriptRoot\..
# Load .env if present
$envFile = Join-Path -Path (Get-Location) -ChildPath ".env"
if (Test-Path $envFile) {
  Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*#') { return }
    if ($_ -match '^\s*$') { return }
    $parts = $_ -split '=', 2
    if ($parts.Length -eq 2) {
      $key = $parts[0].Trim()
      $val = $parts[1].Trim().Trim('"').Trim("'")
      if ($key) { Set-Item -Path "Env:$key" -Value $val }
    }
  }
}

$cacheRoot = Join-Path (Get-Location) ".cache"
$hfHome = Join-Path $cacheRoot "huggingface"
$stHome = Join-Path $cacheRoot "sentence_transformers"
New-Item -ItemType Directory -Path $hfHome -Force | Out-Null
New-Item -ItemType Directory -Path $stHome -Force | Out-Null
$env:HF_HOME = $hfHome
$env:TRANSFORMERS_CACHE = (Join-Path $hfHome "hub")
$env:SENTENCE_TRANSFORMERS_HOME = $stHome

$pythonExe = "python"
if (Test-Path ".\venv_new\Scripts\python.exe") {
    $pythonExe = ".\venv_new\Scripts\python.exe"
    Write-Host "Using venv_new python: $pythonExe"
} elseif (Test-Path ".\venv\Scripts\python.exe") {
    $pythonExe = ".\venv\Scripts\python.exe"
    Write-Host "Using venv python: $pythonExe"
}

# Stop any existing uvicorn api:app instances
try {
    $existing = Get-WmiObject Win32_Process -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*uvicorn api:app*" }
    foreach ($p in $existing) {
        try {
            Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
        } catch {}
    }
} catch {}

# Stop anything currently listening on the target port
try {
    $listenerPid = $null
    try {
        $listenerPid = (Get-NetTCPConnection -LocalAddress $AIHost -LocalPort $AIPort -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty OwningProcess)
    } catch {
        $listenerPid = (Get-NetTCPConnection -LocalPort $AIPort -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty OwningProcess)
    }
    if ($listenerPid) {
        try {
            Stop-Process -Id $listenerPid -Force -ErrorAction SilentlyContinue
        } catch {}
    }
} catch {}

# Start AI service in background with logs
$logsDir = Join-Path (Get-Location) "logs"
try {
  New-Item -ItemType Directory -Path $logsDir -Force -ErrorAction Stop | Out-Null
  $testFile = Join-Path $logsDir ".write_test"
  New-Item -ItemType File -Path $testFile -Force -ErrorAction Stop | Out-Null
  Remove-Item -Force -ErrorAction SilentlyContinue $testFile | Out-Null
} catch {
  $fallbackRoot = $env:TEMP
  if (-not $fallbackRoot) { $fallbackRoot = $env:TMP }
  if (-not $fallbackRoot) { $fallbackRoot = (Get-Location) }
  $logsDir = Join-Path $fallbackRoot "doctorbrain_logs"
  New-Item -ItemType Directory -Path $logsDir -Force -ErrorAction Stop | Out-Null
}
$stamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$outLogPath = Join-Path $logsDir "service_out_$stamp.txt"
$errLogPath = Join-Path $logsDir "service_err_$stamp.txt"
$outLogLatest = Join-Path $logsDir "service_out_latest.txt"
$errLogLatest = Join-Path $logsDir "service_err_latest.txt"

$args = @("-m", "uvicorn", "api:app", "--host", $AIHost, "--port", "$AIPort")
try {
  try {
    New-Item -ItemType File -Path $outLogPath -Force -ErrorAction Stop | Out-Null
    New-Item -ItemType File -Path $errLogPath -Force -ErrorAction Stop | Out-Null
  } catch {
    $fallbackRoot = $env:TEMP
    if (-not $fallbackRoot) { $fallbackRoot = $env:TMP }
    if (-not $fallbackRoot) { $fallbackRoot = (Get-Location) }
    $logsDir = Join-Path $fallbackRoot "doctorbrain_logs"
    New-Item -ItemType Directory -Path $logsDir -Force -ErrorAction Stop | Out-Null
    $outLogPath = Join-Path $logsDir "service_out_$stamp.txt"
    $errLogPath = Join-Path $logsDir "service_err_$stamp.txt"
    $outLogLatest = Join-Path $logsDir "service_out_latest.txt"
    $errLogLatest = Join-Path $logsDir "service_err_latest.txt"
    New-Item -ItemType File -Path $outLogPath -Force -ErrorAction Stop | Out-Null
    New-Item -ItemType File -Path $errLogPath -Force -ErrorAction Stop | Out-Null
  }

  $p = Start-Process -FilePath $pythonExe -ArgumentList $args -WorkingDirectory (Get-Location) -WindowStyle Hidden -PassThru -RedirectStandardOutput $outLogPath -RedirectStandardError $errLogPath -ErrorAction Stop
  Copy-Item -Force -ErrorAction SilentlyContinue $outLogPath $outLogLatest | Out-Null
  Copy-Item -Force -ErrorAction SilentlyContinue $errLogPath $errLogLatest | Out-Null
} catch {
  Write-Host "Failed to start AI service process: $($_.Exception.Message)" -ForegroundColor Red
  exit 1
}

for ($i = 0; $i -lt 30; $i++) {
  try {
    $uri = "http://$($AIHost):$AIPort/health"
    $resp = Invoke-RestMethod -Uri $uri -TimeoutSec 2 -ErrorAction Stop
    if ($resp -and ($resp.status -eq "ok")) {
      exit 0
    }
  } catch {
  }
  Start-Sleep -Seconds 1
}

try {
  if ($p -and $p.HasExited) {
    Write-Host "AI process exited early." -ForegroundColor Red
  }
} catch {
}
Write-Host ("AI service is not listening on {0}:{1}. Check logs: {2}" -f $AIHost, $AIPort, $errLogPath) -ForegroundColor Red
exit 1

