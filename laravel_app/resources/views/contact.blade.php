<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Contact Us - MedAssist</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="antialiased bg-slate-50 text-slate-800">
        <div class="relative min-h-screen bg-slate-50">
            <!-- Navigation -->
            <div class="fixed top-0 right-0 px-6 py-4 z-50 w-full flex justify-between items-center bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
                <a href="{{ url('/') }}" class="font-bold text-blue-900 text-lg flex items-center gap-2">
                    <i class="fa-solid fa-user-doctor"></i> MedAssist
                </a>
                <div class="space-x-4">
                    <a href="{{ route('privacy-policy') }}" class="text-sm text-slate-700 hover:text-blue-900 underline hidden sm:inline">Privacy Policy</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm text-slate-700 hover:text-blue-900 underline">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-slate-700 hover:text-blue-900 underline font-semibold">Log in</a>
                    @endauth
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 sm:p-12">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-blue-900 mb-6">Contact Us</h1>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-lg text-slate-600 mb-6">
                                Have questions or need assistance? We're here to help! Reach out to us through any of the channels below.
                            </p>

                            <div class="space-y-6">
                                <!-- WhatsApp -->
                                <div class="flex items-start gap-4">
                                    <div class="bg-green-100 p-3 rounded-full text-green-600">
                                        <i class="fa-brands fa-whatsapp text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">WhatsApp Support</h3>
                                        <p class="text-slate-600 text-sm mb-1">Chat with our support team instantly.</p>
                                        <a href="https://wa.me/913369028316" target="_blank" class="text-green-600 font-bold hover:underline flex items-center gap-2">
                                            +91 33690 28316 <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Email (Placeholder) -->
                                <div class="flex items-start gap-4">
                                    <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                                        <i class="fa-solid fa-envelope text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">Email Us</h3>
                                        <p class="text-slate-600 text-sm mb-1">Send us your queries anytime.</p>
                                        <a href="mailto:support@niufin.cloud" class="text-blue-600 font-bold hover:underline">
                                            support@niufin.cloud
                                        </a>
                                    </div>
                                </div>

                                <!-- Location (Placeholder) -->
                                <div class="flex items-start gap-4">
                                    <div class="bg-purple-100 p-3 rounded-full text-purple-600">
                                        <i class="fa-solid fa-location-dot text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">Headquarters</h3>
                                        <p class="text-slate-600 text-sm">
                                            MedAssist HQ<br>
                                            Tech Park, Bangalore<br>
                                            India
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-6 rounded-lg border border-slate-100">
                            <h3 class="font-bold text-slate-800 mb-4">Send us a Message</h3>
                            
                            @if(session('success'))
                                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <form action="{{ route('contact.send') }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                                    <input type="text" name="name" required class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border" placeholder="Your Name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input type="email" name="email" required class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border" placeholder="you@example.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                                    <textarea name="message" rows="4" required class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border" placeholder="How can we help?"></textarea>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                                    Send Message
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </body>
</html>
