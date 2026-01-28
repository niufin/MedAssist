<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MedAssist - Login</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="font-sans text-gray-900 antialiased bg-slate-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-6">
                <a href="/" class="flex flex-col items-center gap-2">
                    <div class="bg-blue-900 text-white p-4 rounded-full shadow-lg">
                        <i class="fa-solid fa-user-doctor text-4xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-blue-900">MedAssist</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-2 px-8 py-8 bg-white shadow-xl overflow-hidden sm:rounded-xl border border-gray-100">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
