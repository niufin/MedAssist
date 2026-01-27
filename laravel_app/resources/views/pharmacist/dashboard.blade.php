<x-app-layout>
    @section('title', 'Pharmacist Dashboard - MedAssist')
    
    <x-slot name="header">
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Pharmacist Dashboard') }}
                </h2>
                @if(!empty($stockSummary))
                    <div class="text-xs text-gray-500">
                        {{ $stockSummary['store_name'] ?? 'Pharmacy' }}
                    </div>
                @endif
            </div>

            @if(!empty($stockSummary))
                <div class="flex flex-row w-full gap-4">
                    <div class="flex-1 bg-white border border-gray-200 rounded px-3 py-2">
                        <div class="text-[11px] text-gray-500">Medicines In Stock</div>
                        <div class="text-sm font-bold text-gray-900">{{ number_format($stockSummary['medicines_in_stock'] ?? 0) }}</div>
                    </div>
                    <div class="flex-1 bg-white border border-gray-200 rounded px-3 py-2">
                        <div class="text-[11px] text-gray-500">Low Stock</div>
                        <div class="text-sm font-bold text-orange-700">{{ number_format($stockSummary['low_stock_medicines'] ?? 0) }}</div>
                    </div>
                    <div class="flex-1 bg-white border border-gray-200 rounded px-3 py-2">
                        <div class="text-[11px] text-gray-500">Near Expiry</div>
                        <div class="text-sm font-bold text-red-700">{{ number_format($stockSummary['near_expiry_medicines'] ?? 0) }}</div>
                    </div>
                    <div class="flex-1 bg-white border border-gray-200 rounded px-3 py-2">
                        <div class="text-[11px] text-gray-500">Stock Value</div>
                        <div class="text-sm font-bold text-emerald-700">₹{{ number_format((float) ($stockSummary['stock_value'] ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pharmacy.inventory.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded transition">
                        Inventory
                    </a>
                    <a href="{{ route('pharmacy.reports.stock') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded transition">
                        Stock Report
                    </a>
                    <a href="{{ route('pharmacy.reports.near_expiry') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded transition">
                        Near Expiry
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
            
            <!-- Patient List Sidebar -->
            <div class="w-full lg:w-1/3 bg-white overflow-hidden shadow-sm sm:rounded-lg order-2 lg:order-1">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-bold text-gray-700">Recent Patients</h3>
                </div>
                <div class="overflow-y-auto max-h-[300px] lg:max-h-[600px]">
                    @if($consultations->isEmpty())
                        <div class="p-4 text-gray-500 italic">No pending prescriptions.</div>
                    @else
                        @foreach($consultations as $consultation)
                        <a href="{{ route('pharmacist.dashboard', ['id' => $consultation->id]) }}" class="block p-4 border-b hover:bg-blue-50 transition {{ request('id') == $consultation->id ? 'bg-blue-100 border-l-4 border-l-blue-500' : '' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-gray-800">{{ $consultation->patient_name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">MRN: {{ $consultation->patient->mrn ?? 'N/A' }} • {{ $consultation->patient_age ?? 'N/A' }}</p>
                                </div>
                                <span class="text-[10px] text-gray-400">{{ $consultation->created_at->diffForHumans() }}</span>
                            </div>
                        </a>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Prescription Details -->
            <div class="w-full lg:w-2/3 bg-white overflow-hidden shadow-sm sm:rounded-lg order-1 lg:order-2">
                <div class="p-6 text-gray-900 min-h-[400px]">
                    @if($selectedConsultation)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4 gap-2">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">{{ $selectedConsultation->patient_name }}</h2>
                                <p class="text-sm text-gray-500">MRN: {{ $selectedConsultation->patient->mrn ?? 'N/A' }} • Prescribed by MedAssist • {{ $selectedConsultation->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                @if($selectedConsultation->prescription_path)
                                    <a href="{{ route('prescription.download', $selectedConsultation->id) }}" class="no-loader inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs font-bold rounded shadow hover:bg-blue-700 transition" download>
                                        <i class="fa-solid fa-file-pdf mr-2"></i> Download PDF
                                    </a>
                                @endif
                                <a href="{{ route('pharmacy.dispense.show', $selectedConsultation->id) }}" class="inline-flex items-center justify-center px-3 py-2 bg-emerald-600 text-white text-xs font-bold rounded shadow hover:bg-emerald-700 transition">
                                    <i class="fa-solid fa-pills mr-2"></i> Dispense
                                </a>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold self-start sm:self-auto">
                                    ID: #{{ $selectedConsultation->id }}
                                </span>
                            </div>
                        </div>

                        @php
                            $pData = $selectedConsultation->prescription_data;
                            if (is_string($pData)) {
                                $pData = json_decode($pData, true);
                            }
                            $pData = is_array($pData) ? $pData : [];
                        @endphp
                        
                        @if(isset($pData['medicines']) && count($pData['medicines']) > 0)
                        <div class="bg-gray-50 rounded-lg border p-4">
                            <h4 class="font-bold text-gray-700 mb-3 uppercase text-xs tracking-wider">Prescribed Medicines</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dosage</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($pData['medicines'] as $med)
                                    @php
                                        $keyName = (string) ($med['name'] ?? ($med['brand_name'] ?? ($med['composition_name'] ?? '')));
                                        $status = $fulfillmentStatuses[$keyName] ?? 'pending';
                                        $brand = trim((string) ($med['brand_name'] ?? ''));
                                        $composition = trim((string) ($med['composition_name'] ?? ($brand === '' ? ($med['name'] ?? '') : '')));
                                        $displayName = $brand !== '' ? $brand : ($composition !== '' ? $composition : $keyName);
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
                                        <td class="px-4 py-3 text-gray-600">{{ $med['dosage'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 inline-flex text-[10px] font-bold uppercase leading-5 rounded-full {{ $status === 'given' ? 'bg-green-100 text-green-800' : ($status === 'not_given' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ str_replace('_', ' ', ucfirst($status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <form action="{{ route('pharmacist.fulfill') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="consultation_id" value="{{ $selectedConsultation->id }}">
                                                <input type="hidden" name="medicine_name" value="{{ $keyName }}">
                                                <select name="status" onchange="this.form.submit()" class="text-sm py-2 pl-3 pr-8 border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 cursor-pointer w-full min-w-[120px]">
                                                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="given" {{ $status == 'given' ? 'selected' : '' }}>Given</option>
                                                    <option value="not_given" {{ $status == 'not_given' ? 'selected' : '' }}>Not Given</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        </div>
                        @else
                        <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                            <i class="fa-solid fa-prescription-bottle-medical text-4xl mb-2"></i>
                            <p>No medicines prescribed for this patient.</p>
                        </div>
                        @endif

                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-60">
                            <i class="fa-solid fa-user-doctor text-6xl mb-4"></i>
                            <p class="text-xl font-medium">Select a patient to view prescription</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
