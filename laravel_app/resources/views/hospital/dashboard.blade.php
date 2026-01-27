@section('title', 'Hospital Dashboard - MedAssist')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Hospital Dashboard') }}
            </h2>
            <div class="text-sm text-gray-500 font-semibold">
                {{ auth()->user()->name }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(is_array($summary))
                @php
                    $cards = [
                        ['label' => 'Doctors', 'value' => (int) ($summary['doctors'] ?? 0), 'icon' => 'fa-user-doctor', 'ring' => 'ring-blue-200', 'bg' => 'bg-blue-50', 'fg' => 'text-blue-700'],
                        ['label' => 'Patients', 'value' => (int) ($summary['patients'] ?? 0), 'icon' => 'fa-hospital-user', 'ring' => 'ring-emerald-200', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-700'],
                        ['label' => 'Pharmacists', 'value' => (int) ($summary['pharmacists'] ?? 0), 'icon' => 'fa-prescription-bottle-medical', 'ring' => 'ring-indigo-200', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-700'],
                        ['label' => 'Lab Staff', 'value' => (int) ($summary['lab_assistants'] ?? 0), 'icon' => 'fa-microscope', 'ring' => 'ring-fuchsia-200', 'bg' => 'bg-fuchsia-50', 'fg' => 'text-fuchsia-700'],
                        ['label' => 'Consultations', 'value' => (int) ($summary['consultations'] ?? 0), 'icon' => 'fa-clipboard-list', 'ring' => 'ring-slate-200', 'bg' => 'bg-slate-50', 'fg' => 'text-slate-700'],
                    ];
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:gap-4">
                            @foreach($cards as $card)
                                <div class="flex-1 min-w-[220px] rounded-xl border border-gray-100 bg-gradient-to-br from-white to-gray-50 p-4 shadow-sm hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0">
                                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-500 truncate">{{ $card['label'] }}</div>
                                            <div class="mt-1 text-3xl font-black text-gray-900 leading-none">{{ $card['value'] }}</div>
                                        </div>
                                        <div class="w-12 h-12 rounded-xl {{ $card['bg'] }} {{ $card['fg'] }} flex items-center justify-center ring-1 {{ $card['ring'] }}">
                                            <i class="fa-solid {{ $card['icon'] }} text-lg"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full {{ $card['bg'] }} w-2/3"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex gap-4 overflow-x-auto pb-2">
                        <div class="min-w-[260px] flex-1 border rounded-lg p-4 bg-white hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('doctor.patients.index') }}" class="font-bold text-gray-800 hover:text-blue-800">Patients</a>
                                    <div class="text-xs text-gray-500">View and manage hospital patients</div>
                                </div>
                                <a href="{{ route('doctor.patients.index') }}" class="shrink-0 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-1.5 px-3 rounded text-xs">
                                    Open
                                </a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-800">
                                    Total: {{ (int) ($moduleStats['patients']['total'] ?? 0) }}
                                </span>
                                <a href="{{ route('dashboard') }}" class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-800 hover:bg-blue-100">
                                    Active consults: {{ (int) ($moduleStats['patients']['active_consultations'] ?? 0) }}
                                </a>
                            </div>
                        </div>

                        <div class="min-w-[260px] flex-1 border rounded-lg p-4 bg-white hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('pharmacy.home') }}" class="font-bold text-gray-800 hover:text-blue-800">Pharmacy</a>
                                    <div class="text-xs text-gray-500">Inventory, dispense, invoices</div>
                                </div>
                                <a href="{{ route('pharmacy.home') }}" class="shrink-0 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-1.5 px-3 rounded text-xs">
                                    Open
                                </a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('pharmacy.reports.inventory_overview') }}" class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-50 text-slate-800 hover:bg-slate-100">
                                    In Stock: {{ (int) ($moduleStats['pharmacy']['in_stock'] ?? 0) }}
                                </a>
                                <a href="{{ route('pharmacy.inventory.index', ['near_expiry' => 1]) }}" class="px-2 py-1 text-xs font-semibold rounded-full bg-red-50 text-red-800 hover:bg-red-100">
                                    About to Expire: {{ (int) ($moduleStats['pharmacy']['near_expiry'] ?? 0) }}
                                </a>
                                <a href="{{ route('pharmacy.inventory.index', ['low_stock' => 1]) }}" class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-800 hover:bg-yellow-100">
                                    Low Stock: {{ (int) ($moduleStats['pharmacy']['low_stock'] ?? 0) }}
                                </a>
                            </div>
                        </div>

                        <div class="min-w-[260px] flex-1 border rounded-lg p-4 bg-white hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('lab.dashboard') }}" class="font-bold text-gray-800 hover:text-blue-800">Lab</a>
                                    <div class="text-xs text-gray-500">Upload and view lab reports</div>
                                </div>
                                <a href="{{ route('lab.dashboard') }}" class="shrink-0 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-1.5 px-3 rounded text-xs">
                                    Open
                                </a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-fuchsia-50 text-fuchsia-800">
                                    Reports (7d): {{ (int) ($moduleStats['lab']['reports_7d'] ?? 0) }}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-50 text-slate-800">
                                    Total reports: {{ (int) ($moduleStats['lab']['reports_total'] ?? 0) }}
                                </span>
                            </div>
                        </div>

                        <div class="min-w-[260px] flex-1 border rounded-lg p-4 bg-white hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('dashboard') }}" class="font-bold text-gray-800 hover:text-blue-800">Interactive Mode</a>
                                    <div class="text-xs text-gray-500">Open the interactive consultation screen</div>
                                </div>
                                <a href="{{ route('dashboard') }}" class="shrink-0 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-1.5 px-3 rounded text-xs">
                                    Open
                                </a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-800">
                                    Active: {{ (int) ($moduleStats['interactive']['active'] ?? 0) }}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-50 text-slate-800">
                                    Total: {{ (int) ($moduleStats['interactive']['total'] ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="font-bold text-gray-800">Recent Consultations</div>
                        <a href="{{ route('dashboard') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Open History</a>
                    </div>

                    @if($recentConsultations->isEmpty())
                        <div class="text-sm text-gray-500 italic">No consultations found.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b">
                                        <th class="py-2 pr-4">ID</th>
                                        <th class="py-2 pr-4">Patient</th>
                                        <th class="py-2 pr-4">Doctor</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentConsultations as $c)
                                        <tr class="border-b">
                                            <td class="py-2 pr-4 font-mono text-gray-700">#{{ $c->id }}</td>
                                            <td class="py-2 pr-4 text-gray-800">{{ $c->patient_name ?? ($c->patient->name ?? 'Unknown') }}</td>
                                            <td class="py-2 pr-4 text-gray-800">{{ $c->doctor->name ?? 'N/A' }}</td>
                                            <td class="py-2 pr-4 text-gray-600">{{ $c->status ?? 'N/A' }}</td>
                                            <td class="py-2 pr-4 text-gray-500">{{ $c->created_at?->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
