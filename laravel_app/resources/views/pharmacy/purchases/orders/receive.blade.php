<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Receive Goods') }} • {{ $order->po_no }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $order->supplier?->name ?? '—' }} • {{ $store->name }}
                </div>
            </div>
            <a href="{{ route('pharmacy.purchases.orders.show', $order->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pharmacy.purchases.orders.receive', $order->id) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Rec</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MRP</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($order->items as $it)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $it->medicine_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" name="items[{{ $it->id }}][quantity_received]" value="{{ old('items.' . $it->id . '.quantity_received', $it->quantity) }}" min="0" class="w-24 border-gray-300 rounded shadow-sm">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" name="items[{{ $it->id }}][batch_no]" value="{{ old('items.' . $it->id . '.batch_no') }}" class="w-40 border-gray-300 rounded shadow-sm">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="date" name="items[{{ $it->id }}][expiry_date]" value="{{ old('items.' . $it->id . '.expiry_date') }}" class="w-40 border-gray-300 rounded shadow-sm">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" name="items[{{ $it->id }}][mrp]" value="{{ old('items.' . $it->id . '.mrp') }}" min="0" step="0.01" class="w-28 border-gray-300 rounded shadow-sm">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" name="items[{{ $it->id }}][purchase_price]" value="{{ old('items.' . $it->id . '.purchase_price', $it->unit_cost) }}" min="0" step="0.01" class="w-28 border-gray-300 rounded shadow-sm">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" name="items[{{ $it->id }}][sale_price]" value="{{ old('items.' . $it->id . '.sale_price') }}" min="0" step="0.01" class="w-28 border-gray-300 rounded shadow-sm">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded">
                                Post Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

