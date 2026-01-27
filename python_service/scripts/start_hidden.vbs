Set WshShell = CreateObject("WScript.Shell")
strPath = "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File ""e:\Websites\doctor.niufin.cloud\python_service\scripts\run_doctorbrain.ps1"" -SvcHost 127.0.0.1 -SvcPort 8002"
WshShell.Run strPath, 0, False
