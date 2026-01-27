@php
    $user = Auth::user();
    $activeHospitalId = session('active_hospital_admin_id');
    $activeHospitalName = null;
    if ($user) {
        if ($user->isHospitalAdmin()) {
            $activeHospitalId = $user->id;
            $activeHospitalName = $user->name;
        } elseif ($user->isSuperAdmin() && $activeHospitalId) {
            $activeHospitalName = \App\Models\User::where('id', (int) $activeHospitalId)->value('name');
        }
    }
    // Determine active model for display if session exists (optional, keeping consistent with dashboard)
    $activeModel = null;
    if (isset($session) && $session->chat_history) {
        $decoded = json_decode($session->chat_history, true) ?? [];
        for ($i = count($decoded) - 1; $i >= 0; $i--) {
            $row = $decoded[$i] ?? null;
            if (is_array($row) && ($row['role'] ?? null) === 'assistant') {
                $activeModel = $row['model'] ?? null;
                if ($activeModel && strtolower($activeModel) === 'system') {
                    $activeModel = 'SYSTEM';
                }
                break;
            }
        }
    }
@endphp

<header class="bg-blue-900 text-white p-2 md:p-3 shadow-lg flex justify-between items-center z-20 relative" x-data="{ mobileMenuOpen: false }">
    <div class="flex items-center gap-4">
        <!-- Mobile Toggle -->
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-blue-200 hover:text-white transition">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        
        <!-- Sidebar Toggle (Context Specific) -->
        @if(isset($has_sidebar) && $has_sidebar)
        <button @click="$dispatch('toggle-sidebar')" class="md:hidden text-blue-200 hover:text-white transition ml-3">
            <i class="fa-solid fa-clock-rotate-left text-xl"></i>
        </button>
        @endif

        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 hover:opacity-90 transition">
            <div class="bg-blue-700 p-1.5 rounded-lg"><i class="fa-solid fa-user-md text-lg md:text-xl"></i></div>
            <div>
                <h1 class="text-base md:text-lg font-bold leading-tight">MedAssist</h1>
                <p class="text-blue-300 text-[10px] hidden sm:block">Interactive Diagnostic Mode</p>
            </div>
        </a>

        <!-- Main Navigation Links -->
        <div class="flex items-center gap-1 lg:gap-2 border-l border-blue-800 pl-4">
            <!-- Pharmacy Dropdown -->
            @if($user && ($user->isPharmacist() || $user->isHospitalAdmin() || $user->isAdmin() || $user->isSuperAdmin()))
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-1 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                    <i class="fa-solid fa-prescription-bottle-medical"></i> <span class="hidden lg:inline">Pharmacy</span> <i class="fa-solid fa-chevron-down text-xs ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 origin-top-left" style="display: none;">
                    <a href="{{ $user && $user->isPharmacist() ? route('pharmacist.dashboard') : route('pharmacy.home') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 font-semibold border-b border-gray-100">Dashboard</a>
                    <a href="{{ route('pharmacy.inventory.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Inventory</a>
                    <a href="{{ route('pharmacy.dispense.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Dispense</a>
                    <a href="{{ route('pharmacy.invoices.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Invoices</a>
                    <a href="{{ route('pharmacy.purchases.orders.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Purchases</a>
                    <a href="{{ route('pharmacy.suppliers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Suppliers</a>
                    <a href="{{ route('pharmacy.medicines.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Medicines</a>
                    <a href="{{ route('pharmacy.reports.stock') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Reports</a>
                    @can('isAdmin')
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('pharmacy.settings.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Settings</a>
                    @endcan
                </div>
            </div>
            @endif

            <!-- Admin Users Link -->
            @can('isAdmin')
            <a href="{{ route('admin.users.index') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-users-gear"></i> <span class="hidden lg:inline">Users</span>
            </a>
            @endcan

            @can('isPlatformAdmin')
            <a href="{{ route('admin.hospitals.index') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-hospital"></i> <span class="hidden lg:inline">Hospitals</span>
            </a>
            <a href="{{ route('admin.pharmacies.index') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-store"></i> <span class="hidden lg:inline">Pharmacies</span>
            </a>
            @endcan

            @if($user && $user->isHospitalAdmin())
            <a href="{{ route('hospital.dashboard') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-hospital"></i> <span class="hidden lg:inline">Hospital</span>
            </a>
            @endif

            <!-- Unified Patients Link -->
            @if($user && ($user->can('isDoctor') || $user->can('isHospitalAdmin')))
            <a href="{{ route('doctor.patients.index') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-hospital-user"></i> <span class="hidden lg:inline">Patients</span>
            </a>
            @endif

            <!-- Lab Link -->
            @can('isLabAccess')
            <a href="{{ route('lab.dashboard') }}" class="text-white hover:text-blue-200 text-sm font-semibold flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 transition">
                <i class="fa-solid fa-microscope"></i> <span class="hidden lg:inline">Lab</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Right Side: Status & Profile -->
    <div class="flex items-center gap-2 md:gap-4">
        <!-- Hospital Selector (Super Admin) -->
        @if($user && $user->isSuperAdmin() && request()->routeIs('pharmacy.*'))
            @php
                $hospitals = \App\Models\User::where('role', \App\Models\User::ROLE_HOSPITAL_ADMIN)
                    ->orderBy('name')
                    ->limit(100)
                    ->get(['id', 'name']);
            @endphp
            <div x-data="{ open: false }" class="relative hidden md:block">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 text-xs text-blue-200 hover:text-white border border-blue-700 rounded px-2 py-1 bg-blue-800 hover:bg-blue-700 transition">
                    <span>{{ $activeHospitalName ? 'Hospital: ' . \Illuminate\Support\Str::limit($activeHospitalName, 15) : 'Select Hospital' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px]" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg py-1 z-50 text-gray-700 max-h-96 overflow-y-auto" style="display: none;">
                    @foreach($hospitals as $h)
                        <form method="POST" action="{{ route('pharmacy.context.hospital.set') }}">
                            @csrf
                            <input type="hidden" name="hospital_admin_id" value="{{ $h->id }}">
                            <input type="hidden" name="redirect" value="{{ url()->current() }}">
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-50 {{ (int) $activeHospitalId === (int) $h->id ? 'font-bold text-blue-700 bg-blue-50' : '' }}">
                                {{ $h->name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <a href="{{ route('contact') }}" class="text-blue-200 hover:text-white transition" title="Contact Support">
            <i class="fa-solid fa-circle-question text-lg"></i>
        </a>

        <x-notifications class="text-white" />

        <!-- System Status (Hidden by default, shown via JS if needed) -->
        <div class="hidden md:flex items-center gap-2">
            <div id="health-status" class="hidden flex items-center gap-2 text-[10px]">
                <span class="px-2 py-1 rounded bg-white/10 border border-white/20" data-key="db">DB</span>
                <span class="px-2 py-1 rounded bg-white/10 border border-white/20" data-key="ai_service">AI</span>
                <span id="indexing-status" class="hidden px-2 py-1 rounded bg-blue-600 border border-blue-500 text-white animate-pulse font-bold"><i class="fa-solid fa-arrows-rotate fa-spin mr-1"></i> <span id="indexing-text">Indexing</span></span>
            </div>
            
            @if(isset($activeModel) && $activeModel)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="px-2 py-1 rounded bg-white/10 border border-white/20 text-[10px] flex items-center gap-1 hover:bg-white/20 transition">
                    <span>{{ $activeModel }}</span>
                    <i class="fa-solid fa-circle-info text-[9px] text-blue-200"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-64 bg-white text-gray-800 text-[10px] rounded-lg shadow-lg border border-gray-200 py-2 px-3 z-40">
                    <div class="font-semibold text-[11px] mb-1 text-gray-900">AI Engine Details</div>
                    <div id="ai-model-tooltip-text" class="text-[10px] text-gray-600 leading-snug"></div>
                </div>
            </div>
            @endif
        </div>

        <!-- System Controls (Super Admin Only) -->
        @if($user && $user->isSuperAdmin())
        <div class="hidden md:flex items-center gap-1 border-l border-blue-800 pl-2">
            <form action="{{ route('admin.system.reload_ai') }}" method="POST">
                @csrf
                <button type="submit" class="text-emerald-200 hover:text-white text-xs p-2 rounded hover:bg-white/10 transition" title="Reload Database Cache">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </form>
            <form action="{{ route('admin.system.restart_ai') }}" method="POST" onsubmit="return confirm('Restart AI Service? This will take 10-20 seconds to come back online.');">
                @csrf
                <button type="submit" class="text-orange-300 hover:text-white text-xs p-2 rounded hover:bg-white/10 transition" title="Restart AI Service">
                    <i class="fa-solid fa-power-off"></i>
                </button>
            </form>
        </div>
        @endif

        <!-- New Patient Button -->
        @if($user && ($user->can('isDoctor') || $user->can('isAdmin')))
        <a href="{{ route('new.patient') }}" class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-1.5 px-3 rounded shadow flex items-center gap-2 transition transform hover:scale-105 ml-2">
            <i class="fa-solid fa-plus"></i> <span class="hidden md:inline">New Patient</span>
        </a>
        @endif
        
        <!-- Mobile Right Panel Toggle (Context Specific) -->
        @if(isset($has_right_panel) && $has_right_panel)
        <button @click="$dispatch('toggle-right-panel')" class="lg:hidden text-white ml-1">
            <i class="fa-solid fa-ellipsis-vertical text-xl"></i>
        </button>
        @endif

        @auth
            <div x-data="{ open: false }" class="relative hidden md:block ml-1">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 text-white hover:text-blue-200 focus:outline-none px-2 py-1 rounded hover:bg-white/10 transition">
                    <span class="text-sm font-semibold">{{ Auth::user()->name }}</span>
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 text-gray-700 origin-top-right" style="display: none;">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-blue-50">
                        <i class="fa-solid fa-user mr-2 text-gray-400"></i> Profile
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-red-50 text-red-600">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}" class="hidden md:inline-flex bg-white/10 hover:bg-white/20 text-white text-sm font-bold py-1.5 px-3 rounded border border-white/20 transition">
                Login
            </a>
        @endauth
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="absolute top-full left-0 w-full bg-blue-800 text-white shadow-lg md:hidden z-50 border-t border-blue-700" 
         style="display: none;">
        
        <div class="py-2 space-y-1 px-4">
             <!-- Mobile Links similar to desktop -->
             @if($user && ($user->isPharmacist() || $user->isHospitalAdmin() || $user->isAdmin() || $user->isSuperAdmin()))
                <div class="font-bold text-blue-300 text-xs uppercase mt-2 mb-1">Pharmacy</div>
                <a href="{{ route('pharmacy.dispense.index') }}" class="block py-2 text-sm hover:text-blue-200">Dispense</a>
                <a href="{{ route('pharmacy.inventory.index') }}" class="block py-2 text-sm hover:text-blue-200">Inventory</a>
                <!-- Add other pharmacy links as needed -->
             @endif
             
             @can('isAdmin')
                <a href="{{ route('admin.users.index') }}" class="block py-2 text-sm hover:text-blue-200">Users</a>
             @endcan

             @can('isPlatformAdmin')
                <a href="{{ route('admin.hospitals.index') }}" class="block py-2 text-sm hover:text-blue-200">Hospitals</a>
                <a href="{{ route('admin.pharmacies.index') }}" class="block py-2 text-sm hover:text-blue-200">Pharmacies</a>
             @endcan
             
             @if($user && ($user->can('isDoctor') || $user->can('isHospitalAdmin')))
                <a href="{{ route('doctor.patients.index') }}" class="block py-2 text-sm hover:text-blue-200">Patients</a>
             @endif

             <div class="border-t border-blue-700 my-2"></div>
             @auth
                 <a href="{{ route('profile.edit') }}" class="block py-2 text-sm hover:text-blue-200">Profile</a>
                 <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left py-2 text-sm hover:text-red-300">Logout</button>
                </form>
             @else
                 <a href="{{ route('login') }}" class="block py-2 text-sm hover:text-blue-200">Login</a>
             @endauth
        </div>
    </div>
</header>
