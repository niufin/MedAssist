@section('title', 'Patient Details - MedAssist')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $patient->name }} <span class="text-gray-500 text-sm">({{ $patient->mrn ?? 'No MRN' }})</span>
            </h2>
            <form action="{{ route('doctor.patients.new_consultation', $patient->id) }}" method="POST">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> New Consultation
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Patient Profile Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Patient Profile</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="block text-gray-500 text-xs uppercase font-bold">Full Name</span>
                        <span class="text-gray-900 font-semibold">{{ $patient->name }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase font-bold">Email</span>
                        <span class="text-gray-900">{{ $patient->email }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase font-bold">MRN</span>
                        <span class="text-gray-900 font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $patient->mrn ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase font-bold">Age / Gender</span>
                        <span class="text-gray-900">{{ $patient->age ?? 'N/A' }} / {{ $patient->gender ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase font-bold">Contact</span>
                        <span class="text-gray-900">{{ $patient->contact_number ?? 'N/A' }}</span>
                    </div>
                    <div class="col-span-1 md:col-span-3">
                         <span class="block text-gray-500 text-xs uppercase font-bold">Address / Notes</span>
                         <span class="text-gray-900 italic">{{ $patient->address ?? 'No address recorded.' }}</span>
                    </div>
                </div>
            </div>

            <!-- Consultation History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Consultation History</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($consultations as $consultation)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $consultation->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $consultation->doctor->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @php
                                        $data = $consultation->prescription_data;
                                        if (is_string($data)) {
                                            $data = json_decode($data, true);
                                        }
                                        $diagnosis = $data['diagnosis'] ?? 'Pending';
                                    @endphp
                                    <span class="font-medium">{{ Str::limit($diagnosis, 50) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $consultation->status === 'finished' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($consultation->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="{{ route('dashboard', ['id' => $consultation->id]) }}" class="text-indigo-600 hover:text-indigo-900">Open Chat</a>
                                    @if($consultation->prescription_path)
                                        <a href="{{ route('prescription.download', $consultation->id) }}" class="no-loader text-blue-600 hover:text-blue-900" download><i class="fa-regular fa-file-pdf"></i> PDF</a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 italic">No past consultations found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
