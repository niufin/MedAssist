param(
  [string]$ServiceName = "MedAssistAI",
  [string]$NssmPath = ""
)

$ErrorActionPreference = "Stop"

if (-not $NssmPath) {
  throw "NssmPath is required (full path to nssm.exe)."
}
if (-not (Test-Path $NssmPath)) {
  throw "nssm.exe not found at: $NssmPath"
}

try { & $NssmPath stop $ServiceName | Out-Null } catch {}
try { & $NssmPath remove $ServiceName confirm | Out-Null } catch {}

Write-Host "Removed service '$ServiceName'." -ForegroundColor Green
