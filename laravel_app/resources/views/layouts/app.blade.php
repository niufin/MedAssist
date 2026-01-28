<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'MedAssist'))</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            #global-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(2px);
            }
            .loader-spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .loader-text {
                margin-top: 15px;
                color: #374151;
                font-weight: 600;
                font-size: 1.1em;
            }
            .hidden-loader { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <!-- Global Loader Overlay -->
        <div id="global-loader" class="hidden-loader">
            <div class="loader-spinner"></div>
            <div class="loader-text">Processing...</div>
        </div>

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation_blue')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <span @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <span @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                @endif

                @if ($errors->any())
                    <div x-data="{ show: true }" x-show="show" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Whoops!</strong>
                        <span class="block sm:inline">Something went wrong.</span>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <span @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script>
            // Global Loader Logic
            document.addEventListener('DOMContentLoaded', function() {
                const loader = document.getElementById('global-loader');
                
                // Show loader on form submission
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', function() {
                        // Don't show if the form opens in a new tab or has specific ignore class
                        if (!this.target && !this.classList.contains('no-loader')) {
                            loader.classList.remove('hidden-loader');
                        }
                    });
                });

                // Track clicks on links that should not trigger the loader (e.g. downloads)
                let ignoreUnload = false;
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('a');
                    if (link && (link.classList.contains('no-loader') || link.hasAttribute('download'))) {
                        ignoreUnload = true;
                        // Reset shortly after, just in case
                        setTimeout(() => { ignoreUnload = false; }, 2000);
                    }
                });

                // Show loader on navigating to new pages (optional, can be jarring if fast)
                // Use a small delay so instant interactions don't flash the loader
                window.addEventListener('beforeunload', function() {
                    if (ignoreUnload) return;
                    
                    // Only show if it takes more than 100ms
                    setTimeout(() => {
                        loader.classList.remove('hidden-loader');
                    }, 100);
                });

                // Hide loader when page is fully loaded (if it was stuck)
                window.addEventListener('pageshow', function(event) {
                    if (event.persisted) {
                         loader.classList.add('hidden-loader');
                    }
                });
            });

            // Expose global functions
            window.showLoader = function() {
                document.getElementById('global-loader').classList.remove('hidden-loader');
            }
            window.hideLoader = function() {
                document.getElementById('global-loader').classList.add('hidden-loader');
            }
        </script>
    </body>
</html>
