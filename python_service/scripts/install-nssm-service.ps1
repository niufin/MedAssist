param(
  [string]$ServiceName = "MedAssistAI",
  [string]$NssmPath = "",
  [string]$SvcHost = "127.0.0.1",
  [int]$SvcPort = 8002
)

$ErrorActionPreference = "Stop"

if (-not $NssmPath) {
  throw "NssmPath is required (full path to nssm.exe)."
}
if (-not (Test-Path $NssmPath)) {
  throw "nssm.exe not found at: $NssmPath"
}

$serviceDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$runner = Join-Path $PSScriptRoot "run_doctorbrain.ps1"
if (-not (Test-Path $runner)) {
  throw "run_doctorbrain.ps1 not found at: $runner"
}

$psExe = Join-Path $env:WINDIR "System32\WindowsPowerShell\v1.0\powershell.exe"
if (-not (Test-Path $psExe)) {
  $psExe = "powershell.exe"
}

$programData = $env:ProgramData
if (-not $programData) {
  $programData = $serviceDir
}
$logRoot = Join-Path $programData "MedAssistAI"
$logDir = Join-Path $logRoot "logs"
New-Item -ItemType Directory -Path $logDir -Force | Out-Null

$stdout = Join-Path $logDir "nssm_stdout.log"
$stderr = Join-Path $logDir "nssm_stderr.log"

$args = "-NoProfile -ExecutionPolicy Bypass -File `"$runner`" -SvcHost $SvcHost -SvcPort $SvcPort"

& $NssmPath stop $ServiceName | Out-Null
& $NssmPath remove $ServiceName confirm | Out-Null

& $NssmPath install $ServiceName $psExe $args | Out-Null
& $NssmPath set $ServiceName AppDirectory $serviceDir | Out-Null
& $NssmPath set $ServiceName Start SERVICE_AUTO_START | Out-Null
& $NssmPath set $ServiceName AppStdout $stdout | Out-Null
& $NssmPath set $ServiceName AppStderr $stderr | Out-Null
& $NssmPath set $ServiceName AppRotateFiles 1 | Out-Null
& $NssmPath set $ServiceName AppRotateOnline 1 | Out-Null
& $NssmPath set $ServiceName AppRotateSeconds 86400 | Out-Null
& $NssmPath set $ServiceName AppRotateBytes 10485760 | Out-Null

& $NssmPath start $ServiceName | Out-Null

Write-Host "Installed and started service '$ServiceName' on $SvcHost:$SvcPort." -ForegroundColor Green
