@php
    $user = Auth::user();
    $homeRoute = route('dashboard');
    $homeActive = request()->routeIs('dashboard');
    $activeHospitalId = session('active_hospital_admin_id');
    $activeHospitalName = null;
    if ($user) {
        if ($user->isPharmacist()) {
            $homeRoute = route('pharmacy.home');
            $homeActive = request()->routeIs('pharmacy.*');
        } elseif ($user->isLabAssistant()) {
            $homeRoute = route('lab.dashboard');
            $homeActive = request()->routeIs('lab.*');
        } elseif ($user->isPatient()) {
            $homeRoute = route('patient.dashboard');
            $homeActive = request()->routeIs('patient.*');
        }

        if ($user->isHospitalAdmin()) {
            $activeHospitalId = $user->id;
            $activeHospitalName = $user->name;
        } elseif ($user->isSuperAdmin() && $activeHospitalId) {
            $activeHospitalName = \App\Models\User::where('id', (int) $activeHospitalId)->value('name');
        }
    }
@endphp

<nav
    x-data="{ open: false, hidden: false, lastScrollY: 0 }"
    x-init="lastScrollY = window.scrollY; window.addEventListener('scroll', () => { if (open) return; const y = window.scrollY; hidden = (y > lastScrollY) && (y > 80); lastScrollY = y; })"
    :class="hidden ? '-translate-y-full' : 'translate-y-0'"
    class="fixed top-0 inset-x-0 z-50 bg-white border-b border-gray-100 transform transition-transform duration-200"
>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex justify-between h-12">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $homeRoute }}">
                        <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="$homeRoute" :active="$homeActive">
                        {{ __('Home') }}
                    </x-nav-link>

                    @if($user && $user->isSuperAdmin() && request()->routeIs('pharmacy.*'))
                        @php
                            $hospitals = \App\Models\User::where('role', \App\Models\User::ROLE_HOSPITAL_ADMIN)
                                ->orderBy('name')
                                ->limit(100)
                                ->get(['id', 'name']);
                        @endphp
                        <x-dropdown align="left" width="64">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('pharmacy.*') ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:border-gray-300' }} text-sm font-medium leading-5 bg-white focus:outline-none transition duration-150 ease-in-out">
                                    <div>{{ $activeHospitalName ? ('Hospital: ' . $activeHospitalName) : __('Select Hospital') }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @foreach($hospitals as $h)
                                    <form method="POST" action="{{ route('pharmacy.context.hospital.set') }}">
                                        @csrf
                                        <input type="hidden" name="hospital_admin_id" value="{{ $h->id }}">
                                        <input type="hidden" name="redirect" value="{{ url()->current() }}">
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm leading-5 {{ (int) $activeHospitalId === (int) $h->id ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }} focus:outline-none transition duration-150 ease-in-out">
                                            {{ $h->name }}
                                        </button>
                                    </form>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @can('isPharmacyStaff')
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('pharmacy.*') ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:border-gray-300' }} text-sm font-medium leading-5 bg-white focus:outline-none transition duration-150 ease-in-out">
                                    <div>{{ __('Pharmacy') }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('pharmacy.inventory.index')">
                                    {{ __('Inventory') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.dispense.index')">
                                    {{ __('Dispense') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.invoices.index')">
                                    {{ __('Invoices') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.purchases.orders.index')">
                                    {{ __('Purchases') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.suppliers.index')">
                                    {{ __('Suppliers') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.medicines.index')">
                                    {{ __('Medicines') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('pharmacy.reports.stock')">
                                    {{ __('Reports') }}
                                </x-dropdown-link>
                                @can('isAdmin')
                                    <x-dropdown-link :href="route('pharmacy.settings.edit')">
                                        {{ __('Settings') }}
                                    </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    @endcan

                    @can('isAdmin')
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Manage Users') }}
                        </x-nav-link>
                    @endcan

                    @can('isDoctor')
                        <x-nav-link :href="route('doctor.patients.index')" :active="request()->routeIs('doctor.patients.*')">
                            {{ __('My Patients') }}
                        </x-nav-link>
                        <x-nav-link :href="route('new.patient')" :active="request()->routeIs('new.patient')">
                            {{ __('New Patient') }}
                        </x-nav-link>
                    @endcan

                    @can('isHospitalAdmin')
                        <x-nav-link :href="route('doctor.patients.index')" :active="request()->routeIs('doctor.patients.*')">
                            {{ __('Patients') }}
                        </x-nav-link>
                    @endcan

                    @can('isLabAssistant')
                        <x-nav-link :href="route('lab.dashboard')" :active="request()->routeIs('lab.*')">
                            {{ __('Lab') }}
                        </x-nav-link>
                    @endcan

                    @if($user && $user->isPatient())
                        <x-nav-link :href="route('patient.dashboard')" :active="request()->routeIs('patient.*')">
                            {{ __('Patient') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Notifications -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-notifications class="text-gray-500" />
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-4">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="$homeRoute" :active="$homeActive">
                {{ __('Home') }}
            </x-responsive-nav-link>

            @if($user && $user->isSuperAdmin() && request()->routeIs('pharmacy.*'))
                <div class="px-4 pt-2 text-xs font-bold text-gray-500 uppercase">Hospital</div>
                @php
                    $hospitals = \App\Models\User::where('role', \App\Models\User::ROLE_HOSPITAL_ADMIN)
                        ->orderBy('name')
                        ->limit(100)
                        ->get(['id', 'name']);
                @endphp
                @foreach($hospitals as $h)
                    <form method="POST" action="{{ route('pharmacy.context.hospital.set') }}" class="px-2">
                        @csrf
                        <input type="hidden" name="hospital_admin_id" value="{{ $h->id }}">
                        <input type="hidden" name="redirect" value="{{ url()->current() }}">
                        <button type="submit" class="w-full text-left px-2 py-2 rounded {{ (int) $activeHospitalId === (int) $h->id ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                            {{ $h->name }}
                        </button>
                    </form>
                @endforeach
            @endif

            @can('isAdmin')
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Manage Users') }}
                </x-responsive-nav-link>
            @endcan

            @can('isPharmacyStaff')
                <x-responsive-nav-link :href="route('pharmacy.inventory.index')" :active="request()->routeIs('pharmacy.inventory.*')">
                    {{ __('Pharmacy Inventory') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.dispense.index')" :active="request()->routeIs('pharmacy.dispense.*')">
                    {{ __('Dispense') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.invoices.index')" :active="request()->routeIs('pharmacy.invoices.*')">
                    {{ __('Invoices') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.purchases.orders.index')" :active="request()->routeIs('pharmacy.purchases.*')">
                    {{ __('Purchases') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.suppliers.index')" :active="request()->routeIs('pharmacy.suppliers.*')">
                    {{ __('Suppliers') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.medicines.index')" :active="request()->routeIs('pharmacy.medicines.*')">
                    {{ __('Medicines') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pharmacy.reports.stock')" :active="request()->routeIs('pharmacy.reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
                @can('isAdmin')
                    <x-responsive-nav-link :href="route('pharmacy.settings.edit')" :active="request()->routeIs('pharmacy.settings.*')">
                        {{ __('Pharmacy Settings') }}
                    </x-responsive-nav-link>
                @endcan
            @endcan

            @can('isLabAssistant')
                <x-responsive-nav-link :href="route('lab.dashboard')" :active="request()->routeIs('lab.*')">
                    {{ __('Lab') }}
                </x-responsive-nav-link>
            @endcan

            @can('isDoctor')
                <x-responsive-nav-link :href="route('doctor.patients.index')" :active="request()->routeIs('doctor.patients.*')">
                    {{ __('My Patients') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('new.patient')" :active="request()->routeIs('new.patient')">
                    {{ __('New Patient') }}
                </x-responsive-nav-link>
            @endcan

            @can('isHospitalAdmin')
                <x-responsive-nav-link :href="route('doctor.patients.index')" :active="request()->routeIs('doctor.patients.*')">
                    {{ __('Patients') }}
                </x-responsive-nav-link>
            @endcan

            @if($user && $user->isPatient())
                <x-responsive-nav-link :href="route('patient.dashboard')" :active="request()->routeIs('patient.*')">
                    {{ __('Patient Dashboard') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('notifications.index')">
                    {{ __('Notifications') }}
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
