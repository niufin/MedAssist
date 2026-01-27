$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$vbsPath = Join-Path $scriptDir "start_hidden.vbs"
$regName = "DoctorBrainAI"
$regPath = "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run"

# 1. Update Registry
Write-Host "Updating Registry for Persistence..."
try {
    # Check if entry exists, remove it to be clean
    if (Get-ItemProperty -Path $regPath -Name $regName -ErrorAction SilentlyContinue) {
        Remove-ItemProperty -Path $regPath -Name $regName
    }
    
    # Add new entry pointing to wscript running the vbs
    $command = "wscript.exe `"$vbsPath`""
    New-ItemProperty -Path $regPath -Name $regName -Value $command -PropertyType String | Out-Null
    Write-Host "Registry updated successfully. Service will start on login." -ForegroundColor Green
} catch {
    Write-Error "Failed to update registry: $_"
}

# 2. Stop existing instances
Write-Host "Stopping existing instances..."
$processes = Get-WmiObject Win32_Process | Where-Object { $_.CommandLine -like "*uvicorn api:app*" }
foreach ($p in $processes) {
    Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
}

# 3. Start the hidden service now
Write-Host "Starting service in background..."
Start-Process "wscript.exe" -ArgumentList "`"$vbsPath`""

Write-Host "Done! The AI Service is running in the background." -ForegroundColor Green
Write-Host "Logs are at: e:\Websites\doctor.niufin.cloud\python_service\logs"
