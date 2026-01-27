<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Prescription Preview
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $c->patient_name ?? 'Unknown' }} • MRN: {{ $c->patient?->mrn ?? 'N/A' }}
                </div>
            </div>
            <div class="flex items-center gap-2 no-print">
                <a href="{{ route('prescription.generate', $c->id) }}" class="no-loader bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    PDF
                </a>
                <button type="button" onclick="document.getElementById('rx-frame')?.contentWindow?.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Print
                </button>
                <a href="{{ route('dashboard', ['id' => $c->id]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Back
                </a>
            </div>
        </div>
        <style>
            @media print {
                header { display: none !important; }
                .no-print { display: none !important; }
            }
        </style>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 sm:p-4">
                    <iframe id="rx-frame" src="{{ route('prescription.preview.raw', $c->id) }}" class="w-full rounded border border-gray-200" style="height: 80vh;"></iframe>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

