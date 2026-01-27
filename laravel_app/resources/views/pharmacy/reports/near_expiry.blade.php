<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Near Expiry') }} • {{ $cutoff }}
            </h2>
            <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($batches as $b)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $b->medicine?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $b->batch_no ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $b->expiry_date ? $b->expiry_date->format('d M Y') : '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ $b->quantity_on_hand }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ $b->medicine ? route('pharmacy.inventory.show', $b->medicine->id) : route('pharmacy.inventory.index') }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-3 py-2 rounded-lg text-xs font-bold uppercase transition">
                                                    Open
                                                </a>
                                                <a href="{{ route('pharmacy.stock.adjust', $b->id) }}" class="bg-gray-100 text-gray-800 hover:bg-gray-200 px-3 py-2 rounded-lg text-xs font-bold uppercase transition">
                                                    Adjust
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $batches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
