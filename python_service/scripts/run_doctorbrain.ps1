param(
  [string]$SvcHost = "127.0.0.1",
  [int]$SvcPort = 8002
)
$serviceDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $serviceDir
$cacheRoot = Join-Path (Get-Location) ".cache"
$hfHome = Join-Path $cacheRoot "huggingface"
$stHome = Join-Path $cacheRoot "sentence_transformers"
New-Item -ItemType Directory -Path $hfHome -Force | Out-Null
New-Item -ItemType Directory -Path $stHome -Force | Out-Null
$env:HF_HOME = $hfHome
$env:TRANSFORMERS_CACHE = (Join-Path $hfHome "hub")
$env:SENTENCE_TRANSFORMERS_HOME = $stHome
$python = Join-Path $serviceDir "venv_new\Scripts\python.exe"
if (!(Test-Path $python)) {
  $python = Join-Path $serviceDir "venv\Scripts\python.exe"
}
$logs = Join-Path $serviceDir "logs"
try {
  New-Item -ItemType Directory -Path $logs -Force -ErrorAction Stop | Out-Null
} catch {
  $logs = $null
}

if (-not $logs) {
  $fallbackRoot = $env:TEMP
  if (-not $fallbackRoot) { $fallbackRoot = $env:TMP }
  if (-not $fallbackRoot) { $fallbackRoot = $serviceDir }
  $logs = Join-Path $fallbackRoot "doctorbrain_logs"
  New-Item -ItemType Directory -Path $logs -Force -ErrorAction Stop | Out-Null
}

try {
  $testFile = Join-Path $logs ".write_test"
  New-Item -ItemType File -Path $testFile -Force -ErrorAction Stop | Out-Null
  Remove-Item -Force -ErrorAction SilentlyContinue $testFile | Out-Null
} catch {
  $fallbackRoot = $env:TEMP
  if (-not $fallbackRoot) { $fallbackRoot = $env:TMP }
  if (-not $fallbackRoot) { $fallbackRoot = $serviceDir }
  $logs = Join-Path $fallbackRoot "doctorbrain_logs"
  New-Item -ItemType Directory -Path $logs -Force -ErrorAction Stop | Out-Null
}
while ($true) {
  $stamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
  $logPath = Join-Path $logs "doctorbrain_$stamp.log"
  $logOk = $true
  try {
    $null = New-Item -ItemType File -Path $logPath -Force -ErrorAction Stop
  } catch {
    $logOk = $false
  }
  if ($logOk) {
    & $python -m uvicorn api:app --host $SvcHost --port $SvcPort *>> $logPath
  } else {
    & $python -m uvicorn api:app --host $SvcHost --port $SvcPort
  }
  Start-Sleep -Seconds 2
}
