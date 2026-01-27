@section('title', 'Patient Dashboard - MedAssist')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Patient Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Patient Profile Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-blue-500">
                <div class="p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">{{ auth()->user()->name }}</h2>
                            <p class="text-gray-500 flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-gray-400"></i> {{ auth()->user()->email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Medical Record Number</div>
                        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xl font-mono font-bold shadow-md tracking-widest">
                            {{ auth()->user()->mrn ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connect Visits Card -->
            @if(!auth()->user()->visit_connected)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-green-500">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Connect Past Visits</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        If you have past consultations with a different MRN or before creating this account, enter your previous MRN below to link them. 
                        <strong>This can only be done once.</strong>
                    </p>
                    
                    <form action="{{ route('patient.connect_visits') }}" method="POST" class="flex flex-col sm:flex-row gap-4">
                        @csrf
                        <div class="flex-grow">
                            <input type="text" name="mrn" placeholder="Enter Previous MRN" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition font-bold">
                            Connect Visits
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <div class="flex flex-col lg:flex-row gap-6">
            <div class="w-full lg:w-1/3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-bold text-gray-700">My Visits</h3>
                </div>
                <div class="overflow-y-auto max-h-[300px] lg:max-h-[600px]">
                    @if($consultations->isEmpty())
                        <div class="p-4 text-gray-500 italic">No past consultations found.</div>
                    @else
                        @foreach($consultations as $consultation)
                            <a href="{{ route('patient.dashboard', ['id' => $consultation->id]) }}" class="block p-4 border-b hover:bg-blue-50 transition {{ request('id') == $consultation->id ? 'bg-blue-100 border-l-4 border-l-blue-500' : '' }}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-800">Consultation #{{ $consultation->id }}</p>
                                        <p class="text-xs text-gray-500">{{ $consultation->created_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                    <span class="text-[10px] text-gray-400 uppercase">{{ $consultation->status ?? 'ongoing' }}</span>
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="w-full lg:w-2/3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 min-h-[400px]">
                    @if($selectedConsultation)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4 gap-3">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Consultation Summary</h2>
                                <p class="text-sm text-gray-500">Date: {{ $selectedConsultation->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                @if($selectedConsultation->prescription_path)
                                    <a href="{{ route('prescription.download', $selectedConsultation->id) }}" class="no-loader inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded shadow hover:bg-blue-700 transition" download>
                                        <i class="fa-solid fa-file-pdf mr-2"></i> Download Prescription PDF
                                    </a>
                                @endif
                            </div>
                        </div>

                        @php
                            $pData = $selectedConsultation->prescription_data;
                            if (is_string($pData)) {
                                $pData = json_decode($pData, true);
                            }
                            $pData = $pData ?? [];
                        @endphp

                        <div class="grid grid-cols-1 gap-6 mb-6">
                            <div class="bg-gray-50 rounded-lg border p-4">
                                <h4 class="font-bold text-gray-700 mb-2 uppercase text-xs tracking-wider">Diagnosis</h4>
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $pData['diagnosis'] ?? 'Not available.' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg border p-4">
                                <h4 class="font-bold text-gray-700 mb-2 uppercase text-xs tracking-wider">Doctor Notes</h4>
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $pData['clinical_notes'] ?? $selectedConsultation->ai_analysis ?? 'Not available.' }}</p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h4 class="font-bold text-gray-700 mb-3 uppercase text-xs tracking-wider">Medicines</h4>
                            @if(isset($pData['medicines']) && count($pData['medicines']) > 0)
                                <div class="bg-gray-50 rounded-lg border p-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dosage</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Instructions</th>
                                        </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($pData['medicines'] as $med)
                                            @php
                                                $brand = trim((string) ($med['brand_name'] ?? ''));
                                                $composition = trim((string) ($med['composition_name'] ?? ($brand === '' ? ($med['name'] ?? '') : '')));
                                                $displayName = $brand !== '' ? $brand : ($composition !== '' ? $composition : (string) ($med['name'] ?? ''));
                                                $detailParts = [];
                                                if ($brand !== '') {
                                                    $detailComp = trim((string) ($med['brand_composition_text'] ?? ''));
                                                    if ($detailComp === '') {
                                                        $detailComp = $composition;
                                                    }
                                                    if ($detailComp !== '') $detailParts[] = $detailComp;
                                                    $bs = trim((string) ($med['brand_strength'] ?? ''));
                                                    if ($bs !== '') $detailParts[] = $bs;
                                                    $bf = trim((string) ($med['brand_dosage_form'] ?? ''));
                                                    if ($bf !== '') $detailParts[] = $bf;
                                                }
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    <div>{{ $displayName }}</div>
                                                    @if(!empty($detailParts))
                                                        <div class="text-xs text-gray-500 mt-1">({{ implode(' • ', $detailParts) }})</div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-700">{{ $med['dosage'] ?? '' }} {{ $med['frequency'] ?? '' }} {{ $med['duration'] ?? '' }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ $med['instruction'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-gray-400 text-sm">No medicines recorded for this consultation.</div>
                            @endif
                        </div>

                        <div class="mb-8">
                            <h4 class="font-bold text-gray-700 mb-2 uppercase text-xs tracking-wider">Advice</h4>
                            <div class="bg-gray-50 rounded-lg border p-4">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $pData['advice'] ?? 'No specific advice recorded.' }}</p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h4 class="font-bold text-gray-700 mb-2 uppercase text-xs tracking-wider">Requested Investigations</h4>
                            <div class="bg-gray-50 rounded-lg border p-4">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $pData['investigations'] ?? 'No investigations requested.' }}</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-700 mb-3 uppercase text-xs tracking-wider">Lab Reports</h4>
                            @if($selectedConsultation->labReports && $selectedConsultation->labReports->count() > 0)
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($selectedConsultation->labReports as $report)
                                        <div class="flex items-center justify-between bg-white border p-4 rounded-lg hover:bg-gray-50 transition">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-red-50 p-3 rounded-lg">
                                                    <i class="fa-regular fa-file-pdf text-red-500 text-xl"></i>
                                                </div>
                                                <div>
                                                    <a href="{{ route('lab.report.view', $report->id) }}" target="_blank" class="text-blue-700 font-bold hover:underline">
                                                        View Report
                                                    </a>
                                                    <p class="text-xs text-gray-500">{{ $report->created_at->format('d M Y, h:i A') }} • {{ $report->notes ?? 'No notes' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-gray-400 text-sm">No lab reports attached to this consultation.</div>
                            @endif
                        </div>

                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-60">
                            <i class="fa-solid fa-user text-6xl mb-4"></i>
                            <p class="text-xl font-medium">Select a visit from the left to see details</p>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
