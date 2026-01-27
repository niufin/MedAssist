param(
  [string]$TaskName = "MedAssistService",
  [string]$AiHostParam = "127.0.0.1",
  [int]$AiPortParam = 8002
)
$runner = Join-Path $PSScriptRoot "run_doctorbrain.ps1"
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$runner`" -SvcHost $AiHostParam -SvcPort $AiPortParam"
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)

try {
  $trigger = New-ScheduledTaskTrigger -AtStartup
  $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
  Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force -ErrorAction Stop
  Write-Host "Registered scheduled task '$TaskName' at startup (SYSTEM)." -ForegroundColor Green
  exit 0
} catch {
  Write-Host "Startup task registration failed (requires admin)." -ForegroundColor Yellow
  Write-Host "For boot without login, run PowerShell as Administrator and execute:" -ForegroundColor Yellow
  Write-Host "  $PSScriptRoot\register-ai-task-startup-admin.ps1 -TaskName $TaskName -AiHostParam $AiHostParam -AiPortParam $AiPortParam" -ForegroundColor Yellow
  Write-Host "Falling back to logon task." -ForegroundColor Yellow
}

try {
  $trigger = New-ScheduledTaskTrigger -AtLogOn
  $principal = New-ScheduledTaskPrincipal -UserId $env:UserName -RunLevel Limited
  Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force -ErrorAction Stop
  Write-Host "Registered scheduled task '$TaskName' at logon ($($env:UserName))." -ForegroundColor Green
  exit 0
} catch {
  Write-Host "Logon task registration failed. Falling back to HKCU Run persistence (starts after login)." -ForegroundColor Yellow
}

$regName = "DoctorBrainAI"
$regPath = "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run"
$vbsPath = Join-Path $PSScriptRoot "start_hidden.vbs"
$command = "wscript.exe `"$vbsPath`""
try {
  if (Get-ItemProperty -Path $regPath -Name $regName -ErrorAction SilentlyContinue) {
    Remove-ItemProperty -Path $regPath -Name $regName -ErrorAction SilentlyContinue
  }
  New-ItemProperty -Path $regPath -Name $regName -Value $command -PropertyType String -Force | Out-Null
  Write-Host "Registered HKCU Run entry '$regName'." -ForegroundColor Green
  exit 0
} catch {
  Write-Host "Failed to register auto-start. Please run this script as Administrator to register an AtStartup task." -ForegroundColor Red
  exit 1
}

