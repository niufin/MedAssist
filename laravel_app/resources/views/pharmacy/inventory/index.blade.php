<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Pharmacy Inventory') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.dispense.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Dispense
                </a>
                <a href="{{ route('pharmacy.invoices.index') }}" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">
                    Invoices
                </a>
                <a href="{{ route('pharmacy.purchases.orders.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    Purchases
                </a>
                <a href="{{ route('pharmacy.reports.stock') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Reports
                </a>
                @can('isAdmin')
                    <a href="{{ route('pharmacy.settings.edit') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                        Settings
                    </a>
                @endcan
                <a href="{{ route('pharmacy.stock.in') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                    Stock In
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <form method="GET" action="{{ route('pharmacy.inventory.index') }}" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Search brand, ingredient, manufacturer..." class="w-full sm:w-96 border-gray-300 rounded shadow-sm">
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
                        @if($search !== '')
                            <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-center">
                                Clear
                            </a>
                        @endif
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Composition</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alerts</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $m)
                                    @php
                                        $onHand = (int) $m->on_hand;
                                        $nearExpiry = (int) $m->near_expiry_on_hand;
                                        $lowStock = $onHand > 0 && $onHand <= $store->low_stock_threshold;
                                        $gd = $m->generic_display;
                                        $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
                                        $expiryDates = $m->stockBatches->pluck('expiry_date')->unique()->sort();
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4">
                                            <a class="font-medium text-blue-700 hover:text-blue-900 block" href="{{ route('pharmacy.inventory.show', $m->id) }}">
                                                {{ $m->name }} @if($m->strength) <span class="text-gray-500">({{ $m->strength }})</span> @endif
                                            </a>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $m->manufacturer_name ?? '—' }}
                                                @if($m->therapeutic_class) • {{ $m->therapeutic_class }} @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs break-words">
                                            {{ $compText ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                            @forelse($expiryDates as $date)
                                                <div class="{{ $date && $date <= now()->addDays($store->near_expiry_days) ? 'text-red-600 font-bold' : '' }}">
                                                    {{ $date ? $date->format('d M Y') : 'No Expiry' }}
                                                </div>
                                            @empty
                                                <span class="text-gray-400">—</span>
                                            @endforelse
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                            {{ number_format($m->price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ $onHand }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-2">
                                                @if($lowStock)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Low stock
                                                    </span>
                                                @endif
                                                @if($nearExpiry > 0)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                        Near expiry
                                                    </span>
                                                @endif
                                                @if(!$lowStock && $nearExpiry === 0)
                                                    <span class="text-xs text-gray-500">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
