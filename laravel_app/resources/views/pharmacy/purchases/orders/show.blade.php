<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $order->po_no }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $order->supplier?->name ?? '—' }} • Status: {{ ucfirst($order->status) }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.purchases.orders.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Back
                </a>
                @if($order->status === 'draft')
                    <form method="POST" action="{{ route('pharmacy.purchases.orders.submit', $order->id) }}">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                            Submit
                        </button>
                    </form>
                @endif
                @if(in_array($order->status, ['draft','ordered'], true))
                    <a href="{{ route('pharmacy.purchases.orders.receive.form', $order->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Receive
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="font-bold text-gray-800 mb-3">Items</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($order->items as $it)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $it->medicine_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $it->quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ number_format((float) $it->unit_cost, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ number_format((float) $it->line_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="4">No items yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($order->status === 'draft')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 space-y-4">
                        <div class="font-bold text-gray-800">Add Item</div>

                        <form method="GET" action="{{ route('pharmacy.purchases.orders.show', $order->id) }}" class="flex flex-col sm:flex-row gap-2">
                            <input type="text" name="q" value="{{ $search }}" placeholder="Search medicine..." class="w-full sm:w-96 border-gray-300 rounded shadow-sm">
                            <select name="search_by" class="w-full sm:w-64 border-gray-300 rounded shadow-sm">
                                <option value="all" {{ ($searchBy ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="brand" {{ ($searchBy ?? 'all') === 'brand' ? 'selected' : '' }}>Brand name</option>
                                <option value="name" {{ ($searchBy ?? 'all') === 'name' ? 'selected' : '' }}>Medicine name</option>
                                <option value="composition" {{ ($searchBy ?? 'all') === 'composition' ? 'selected' : '' }}>Composition / ingredients</option>
                                <option value="strength" {{ ($searchBy ?? 'all') === 'strength' ? 'selected' : '' }}>Strength</option>
                                <option value="manufacturer" {{ ($searchBy ?? 'all') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                                <option value="class" {{ ($searchBy ?? 'all') === 'class' ? 'selected' : '' }}>Therapeutic class</option>
                            </select>
                            <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">
                                Search
                            </button>
                        </form>

                        @if($search !== '')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @forelse($medicines as $m)
                                    <form method="POST" action="{{ route('pharmacy.purchases.orders.items.add', $order->id) }}" class="border rounded p-3 space-y-2">
                                        @csrf
                                        <input type="hidden" name="medicine_id" value="{{ $m->id }}">
                                        <div class="font-bold text-gray-900">
                                            {{ $m->brand_name ?: $m->name }}
                                            @if($m->strength) <span class="text-gray-500">({{ $m->strength }})</span> @endif
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            {{ $m->composition_text ?? '—' }}
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="number" name="quantity" value="1" min="1" class="border-gray-300 rounded shadow-sm" required>
                                            <input type="number" name="unit_cost" value="0" min="0" step="0.01" class="border-gray-300 rounded shadow-sm">
                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                Add
                                            </button>
                                        </div>
                                    </form>
                                @empty
                                    <div class="text-sm text-gray-500">No matches.</div>
                                @endforelse
                            </div>
                        @endif

                        <form method="POST" action="{{ route('pharmacy.purchases.orders.items.add', $order->id) }}" class="border rounded p-4 space-y-3">
                            @csrf
                            <div class="font-bold text-gray-700 text-sm">Manual Entry</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Medicine Name</label>
                                    <input type="text" name="medicine_name" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" name="quantity" value="1" min="1" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unit Cost</label>
                                    <input type="number" name="unit_cost" value="0" min="0" step="0.01" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                        Add Item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
