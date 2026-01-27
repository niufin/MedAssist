param(
  [string]$TaskName = "MedAssistService",
  [string]$AiHostParam = "127.0.0.1",
  [int]$AiPortParam = 8002
)

$ErrorActionPreference = "Stop"

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
  throw "Run PowerShell as Administrator to register an AtStartup SYSTEM task (boot without login)."
}

$runner = Join-Path $PSScriptRoot "run_doctorbrain.ps1"
if (-not (Test-Path $runner)) {
  throw "run_doctorbrain.ps1 not found at: $runner"
}

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$runner`" -SvcHost $AiHostParam -SvcPort $AiPortParam"
$trigger = New-ScheduledTaskTrigger -AtStartup
$taskPrincipal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $taskPrincipal -Settings $settings -Force -ErrorAction Stop

Write-Host "Registered scheduled task '$TaskName' at startup (SYSTEM)." -ForegroundColor Green
