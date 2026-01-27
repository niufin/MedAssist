# Windows Service (NSSM)

## Install
1. Download NSSM and get the full path to `nssm.exe`.
2. Open **PowerShell as Administrator**.
3. Run:

```powershell
$RepoRoot = "C:\path\to\MedAssist"
& "$RepoRoot\python_service\scripts\install-nssm-service.ps1" `
  -ServiceName "MedAssistAI" `
  -NssmPath "C:\path\to\nssm.exe" `
  -SvcHost "127.0.0.1" `
  -SvcPort 8002
```

Logs are written to:
- `C:\ProgramData\MedAssistAI\logs\nssm_stdout.log`
- `C:\ProgramData\MedAssistAI\logs\nssm_stderr.log`

## Uninstall
Open **PowerShell as Administrator** and run:

```powershell
$RepoRoot = "C:\path\to\MedAssist"
& "$RepoRoot\python_service\scripts\uninstall-nssm-service.ps1" `
  -ServiceName "MedAssistAI" `
  -NssmPath "C:\path\to\nssm.exe"
```
