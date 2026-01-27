<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Dispensing Queue') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }}
                </div>
            </div>
            <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Inventory
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($consultations as $c)
                            @php
                                $order = $orderByConsultation->get($c->id);
                                $status = $order?->status ?? 'no_order';
                            @endphp
                            <a href="{{ route('pharmacy.dispense.show', $c->id) }}" class="border rounded-lg p-4 hover:bg-blue-50 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $c->patient_name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            MRN: {{ $c->patient->mrn ?? 'N/A' }} • {{ $c->created_at->format('d M Y, h:i A') }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Doctor: {{ $c->doctor?->name ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        @if($status === 'dispensed')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Dispensed</span>
                                        @elseif($status === 'open')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Open</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">New</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div>
                        {{ $consultations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

