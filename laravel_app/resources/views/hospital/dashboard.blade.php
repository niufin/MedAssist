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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('doctor.patients.index') }}" class="block border rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="font-bold text-gray-800">Patients</div>
                            <div class="text-xs text-gray-500">View and manage hospital patients</div>
                        </a>
                        <a href="{{ route('pharmacy.home') }}" class="block border rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="font-bold text-gray-800">Pharmacy</div>
                            <div class="text-xs text-gray-500">Inventory, dispense, invoices</div>
                        </a>
                        <a href="{{ route('lab.dashboard') }}" class="block border rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="font-bold text-gray-800">Lab</div>
                            <div class="text-xs text-gray-500">Upload and view lab reports</div>
                        </a>
                        <a href="{{ route('dashboard') }}" class="block border rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="font-bold text-gray-800">Interactive Mode</div>
                            <div class="text-xs text-gray-500">Open the interactive consultation screen</div>
                        </a>
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
