# Contributing

## Setup

### Laravel (Web + API)
- `cd laravel_app`
- `composer install`
- `npm install && npm run build`
- `cp .env.example .env` and configure DB
- `php artisan key:generate`
- `php artisan migrate --seed`
- `php artisan serve`

### Python AI Service
- `cd python_service`
- `python -m venv venv`
- Activate venv and `pip install -r requirements.txt`
- `cp .env.example .env` and set `OPENROUTER_API_KEY`
- `uvicorn api:app --reload --host 127.0.0.1 --port 8002`

### Android (WebView Client)
- Open `android_app/` in Android Studio.
- Build and run on an emulator/device.

## Guidelines
- Do not commit `.env`, keystores, or any private PDFs/datasets.
- Add tests for behavioral changes where possible.

