<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $medicine->name }} @if($medicine->strength) <span class="text-gray-500">({{ $medicine->strength }})</span> @endif
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.stock.in', ['medicine_id' => $medicine->id]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                    Stock In
                </a>
                <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="font-bold text-gray-700 mb-3">Batches</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MRP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">On Hand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($batches as $b)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $b->batch_no ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $b->expiry_date ? $b->expiry_date->format('d M Y') : '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $b->mrp !== null ? number_format((float) $b->mrp, 2) : '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $b->sale_price !== null ? number_format((float) $b->sale_price, 2) : '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ $b->quantity_on_hand }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('pharmacy.stock.adjust', $b->id) }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition">
                                                Adjust
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="6">No batches yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="font-bold text-gray-700 mb-3">Recent Movements</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($movements as $mv)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $mv->created_at->format('d M Y, h:i A') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ ucfirst(str_replace('_', ' ', $mv->movement_type)) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold {{ $mv->quantity >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                            {{ $mv->quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $mv->stock_batch_id ?? '—' }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $mv->notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="5">No movements yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

