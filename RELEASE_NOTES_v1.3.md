# Niufin Doctor v1.3 Release Notes

## 🚀 Overview
Version 1.3 focuses on critical stability improvements for the Python AI Service and enhances the accuracy of AI-generated prescriptions. This release ensures the AI service runs reliably in the background without depending on open console windows and resolves local connectivity issues.

## ✨ Key Features & Improvements

### 🧠 AI Service Stability
- **Background Execution**: The AI service now launches with `WindowStyle Hidden`, eliminating the need for an open PowerShell console window.
- **Process Management**: Improved startup scripts (`start-ai.ps1`) now automatically detect and terminate stale `uvicorn` processes before starting a new instance.
- **Log Redirection**: Standard Output (stdout) and Standard Error (stderr) are now written to separate log files in `python_service/logs/`, preventing file lock conflicts.

### 💊 Enhanced Prescription Logic
- **Data-Driven Prescriptions**: The AI now strictly references local JSON data (`medicines_nlem.json`) for medicine availability, reducing hallucinations.
- **Strict Evidence Prioritization**: The system prompt has been updated to prioritize **Lab Data** > **Local Context (PDFs)** > **General Knowledge**.
- **Medicine Database**: Added `medicines_extended.json` to the `python_service/data/` directory for future expansion of the drug database.

### 🛠️ Bug Fixes
- **Reload DB Connection Error**: Resolved `cURL error 7` (Failed to connect to port 8002) by standardizing the port configuration across all startup scripts (`start_hidden.vbs`, `run_doctorbrain.ps1`).
- **Environment Consistency**: All scripts now explicitly use the dedicated `venv_new` virtual environment to prevent dependency mismatches.

## 📂 Technical Details
- **Updated Files**:
  - `python_service/api.py`: Updated system prompts and medicine loading logic.
  - `python_service/scripts/start-ai.ps1`: Added process cleanup and hidden window logic.
  - `python_service/scripts/run_doctorbrain.ps1`: Port and venv path corrections.
  - `python_service/scripts/start_hidden.vbs`: Updated to pass correct arguments to the runner.
- **New Files**:
  - `python_service/data/medicines_extended.json`: Extended medicine list.

## 📦 Deployment
To deploy this update on the Windows Server:
1. Pull the latest changes from `main`.
2. Run `python_service/scripts/start-ai.ps1` to restart the service with the new configuration.