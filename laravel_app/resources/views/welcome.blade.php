<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MedAssist - Intelligent Healthcare</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="antialiased bg-slate-50 text-slate-800">
        <div class="relative flex items-top justify-center min-h-screen sm:items-center py-4 sm:pt-0">
            @if (Route::has('login'))
                <div class="fixed top-0 right-0 px-6 py-4 sm:block z-50 w-full sm:w-auto flex justify-between sm:justify-end items-center bg-white/90 sm:bg-transparent backdrop-blur sm:backdrop-blur-none border-b sm:border-none border-slate-200 shadow-sm sm:shadow-none">
                     <span class="sm:hidden font-bold text-blue-900 text-lg"><i class="fa-solid fa-user-doctor mr-2"></i>MedAssist</span>
                    <div class="space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm text-slate-700 dark:text-slate-500 underline">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-slate-700 dark:text-slate-500 underline font-semibold">Log in</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 text-sm text-slate-700 dark:text-slate-500 underline font-semibold">Register</a>
                            @endif
                        @endauth
                    </div>
                </div>
            @endif

            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 mt-20 sm:mt-0">
                <div class="flex flex-col items-center justify-center pt-8 sm:pt-0">
                     <div class="bg-blue-900 text-white p-6 rounded-full shadow-2xl mb-8 animate-bounce">
                        <i class="fa-solid fa-user-doctor text-6xl"></i>
                    </div>
                    <h1 class="text-4xl sm:text-6xl font-extrabold text-blue-900 tracking-tight text-center mb-4">
                        MedAssist
                    </h1>
                    <p class="text-xl sm:text-2xl text-slate-600 text-center max-w-2xl px-4">
                        The intelligent healthcare management system for modern medical facilities.
                    </p>
                </div>

                <div class="mt-12 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                        <div class="p-8 text-center hover:bg-slate-50 transition duration-300">
                            <div class="text-blue-500 mb-4">
                                <i class="fa-solid fa-stethoscope text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">For Doctors</h3>
                            <p class="text-slate-600">
                                AI-powered diagnosis assistance, instant prescription generation, and patient history management.
                            </p>
                        </div>

                        <div class="p-8 text-center hover:bg-slate-50 transition duration-300">
                            <div class="text-green-500 mb-4">
                                <i class="fa-solid fa-pills text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">For Pharmacists</h3>
                            <p class="text-slate-600">
                                Digital prescription fulfillment, inventory tracking, and seamless doctor collaboration.
                            </p>
                        </div>

                        <div class="p-8 text-center hover:bg-slate-50 transition duration-300">
                            <div class="text-purple-500 mb-4">
                                <i class="fa-solid fa-flask text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">For Labs</h3>
                            <p class="text-slate-600">
                                Direct report uploads, OCR analysis integration, and streamlined test management.
                            </p>
                        </div>
                    </div>
                    
                    <div class="p-8 bg-slate-50 border-t border-slate-100 text-center">
                         <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 shadow-lg transform transition hover:scale-105">
                            Get Started
                        </a>
                    </div>
                </div>

                <div class="mt-10 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-8 border-b border-slate-100 text-center">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">
                            Downloads
                        </h2>
                        <p class="mt-3 text-slate-600 max-w-2xl mx-auto">
                            Download the latest MedAssist apps for Windows and Android.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                        <div class="p-8">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                                    <i class="fa-brands fa-windows text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-slate-800">Windows Desktop</h3>
                                    @if(!empty($windowsDownload))
                                        <p class="mt-2 text-sm text-slate-600 break-all">
                                            {{ $windowsDownload['filename'] }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Updated {{ $windowsDownload['updated_at']->toFormattedDateString() }} · {{ number_format($windowsDownload['size_bytes'] / 1024 / 1024, 1) }} MB
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ asset($windowsDownload['relative_path']) }}" class="inline-flex items-center justify-center px-6 py-3 rounded-md text-white bg-blue-600 hover:bg-blue-700 shadow-lg">
                                                Download MSI
                                            </a>
                                        </div>
                                    @else
                                        <p class="mt-2 text-slate-600">
                                            No Windows installer available yet.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-8">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-green-50 text-green-700 flex items-center justify-center">
                                    <i class="fa-brands fa-android text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-slate-800">Android</h3>
                                    @if(!empty($androidDownload))
                                        <p class="mt-2 text-sm text-slate-600 break-all">
                                            {{ $androidDownload['filename'] }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Updated {{ $androidDownload['updated_at']->toFormattedDateString() }} · {{ number_format($androidDownload['size_bytes'] / 1024 / 1024, 1) }} MB
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ asset($androidDownload['relative_path']) }}" class="inline-flex items-center justify-center px-6 py-3 rounded-md text-white bg-green-600 hover:bg-green-700 shadow-lg">
                                                Download APK
                                            </a>
                                        </div>
                                    @else
                                        <p class="mt-2 text-slate-600">
                                            No Android APK available yet.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-8 sm:items-center sm:justify-between px-4">
                    <div class="text-center text-sm text-slate-500 sm:text-left">
                        <div class="flex items-center gap-4">
                            <a href="https://niufin.cloud" class="group inline-flex items-center hover:text-slate-700 focus:outline-none focus:text-slate-700">
                                <span class="font-bold text-blue-900">NiuFin Cloud</span>
                            </a>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('privacy-policy') }}" class="text-slate-500 hover:text-slate-700 underline">Privacy Policy</a>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('contact') }}" class="text-slate-500 hover:text-slate-700 underline">Contact Us</a>
                        </div>
                    </div>

                    <div class="ml-4 text-center text-sm text-slate-500 sm:text-right sm:ml-0">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
