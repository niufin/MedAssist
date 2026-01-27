@section('title', 'Lab Dashboard - MedAssist')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lab Assistant Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
            
            <!-- Patient List Sidebar -->
            <div class="w-full lg:w-1/3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-bold text-gray-700">Recent Patients</h3>
                </div>
                <div class="overflow-y-auto max-h-[300px] lg:max-h-[600px]">
                    @if($consultations->isEmpty())
                        <div class="p-4 text-gray-500 italic">No patients found.</div>
                    @else
                        @foreach($consultations as $consultation)
                        <a href="{{ route('lab.dashboard', ['id' => $consultation->id]) }}" class="block p-4 border-b hover:bg-blue-50 transition {{ request('id') == $consultation->id ? 'bg-blue-100 border-l-4 border-l-blue-500' : '' }}">
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

            <!-- Lab Details & Upload -->
            <div class="w-2/3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 min-h-[400px]">
                    @if($selectedConsultation)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4 gap-3">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">{{ $selectedConsultation->patient_name }}</h2>
                                <p class="text-sm text-gray-500">MRN: {{ $selectedConsultation->patient->mrn ?? 'N/A' }} • Referred by MedAssist • {{ $selectedConsultation->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                @if(!empty($selectedConsultation->prescription_data))
                                    <a href="{{ route('prescription.preview', $selectedConsultation->id) }}" target="_blank" class="no-loader inline-flex items-center justify-center px-4 py-2 bg-blue-700 text-white text-sm font-bold rounded-md shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                        <i class="fa-solid fa-file-medical mr-2"></i> View Prescription
                                    </a>
                                @endif
                                @if($selectedConsultation->prescription_path)
                                    <a href="{{ route('prescription.download', $selectedConsultation->id) }}" class="no-loader inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-md shadow-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition" download>
                                        <i class="fa-solid fa-file-pdf mr-2"></i> Download PDF
                                    </a>
                                @endif
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-bold">
                                    ID: #{{ $selectedConsultation->id }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Requested Investigations</h4>
                                @php
                                    $hasReports = $selectedConsultation->labReports && $selectedConsultation->labReports->count() > 0;
                                    $labStatus = $hasReports ? 'Report Uploaded' : 'Pending';
                                    $statusClasses = $hasReports
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $statusClasses }}">
                                    {{ $labStatus }}
                                </span>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg border">
                                @php
                                    $pData = $selectedConsultation->prescription_data;
                                    if (is_string($pData)) {
                                        $pData = json_decode($pData, true);
                                    }
                                @endphp
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $pData['investigations'] ?? 'No specific investigations requested.' }}</p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h4 class="font-bold text-gray-700 mb-3 uppercase text-xs tracking-wider">Attached Reports</h4>
                            @if($selectedConsultation->labReports && $selectedConsultation->labReports->count() > 0)
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach($selectedConsultation->labReports as $report)
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white border p-4 rounded-lg hover:bg-gray-50 transition gap-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="bg-red-50 p-3 rounded-lg"><i class="fa-regular fa-file-pdf text-red-500 text-2xl"></i></div>
                                            <div>
                                                <a href="{{ route('lab.report.view', $report->id) }}" target="_blank" class="text-blue-700 font-bold hover:underline text-base block mb-1">
                                                    View Report
                                                </a>
                                                <span class="text-xs text-gray-500 block">{{ $report->created_at->format('d M Y, h:i A') }} • {{ $report->notes ?? 'No notes' }}</span>
                                            </div>
                                        </div>
                                        <form action="{{ route('lab.report.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this report?');" class="w-full sm:w-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full sm:w-auto text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 py-2 px-4 rounded-lg transition text-sm font-bold flex items-center justify-center gap-2">
                                                <i class="fa-solid fa-trash-can"></i> <span class="sm:hidden">Delete Report</span>
                                            </button>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-6 bg-gray-50 border border-dashed rounded text-gray-400 text-sm">
                                    No reports uploaded yet.
                                </div>
                            @endif
                        </div>

                        <div class="border-t pt-6">
                            <h4 class="font-bold text-gray-700 mb-3 uppercase text-xs tracking-wider">Upload New Report</h4>
                            <form action="{{ route('lab.upload') }}" method="POST" enctype="multipart/form-data" class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                @csrf
                                <input type="hidden" name="consultation_id" value="{{ $selectedConsultation->id }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Select File (PDF, JPG, PNG - Max {{ ini_get('upload_max_filesize') }})</label>
                                        <input type="file" name="report_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                        <input type="text" name="notes" placeholder="e.g., Blood Test Results" class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <div class="mt-4 text-right">
                                    <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded shadow flex items-center justify-center gap-2 ml-auto">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Report
                                    </button>
                                </div>
                            </form>
                        </div>

                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-60">
                            <i class="fa-solid fa-microscope text-6xl mb-4"></i>
                            <p class="text-xl font-medium">Select a patient to view details</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
