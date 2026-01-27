<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Stock In') }}
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
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('pharmacy.stock.in') }}" class="flex flex-col sm:flex-row gap-2">
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
                        @if($search !== '')
                            <a href="{{ route('pharmacy.stock.in') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-center">
                                Clear
                            </a>
                        @endif
                    </form>

                    @if($search !== '')
                        <div class="mt-4">
                            <div class="text-sm font-bold text-gray-700 mb-2">Select a medicine</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @forelse($medicines as $m)
                                    <a href="{{ route('pharmacy.stock.in', ['medicine_id' => $m->id]) }}" class="border rounded p-3 hover:bg-blue-50 transition">
                                        <div class="font-bold text-gray-900">
                                            {{ $m->brand_name ?: $m->name }}
                                            @if($m->strength) <span class="text-gray-500">({{ $m->strength }})</span> @endif
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">
                                            {{ $m->composition_text ?? '—' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $m->therapeutic_class ?? '—' }}
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-sm text-gray-500">No medicines found.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($medicine)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="font-bold text-gray-800 mb-4">
                            {{ $medicine->name }} @if($medicine->strength) <span class="text-gray-500">({{ $medicine->strength }})</span> @endif
                        </div>

                        <form method="POST" action="{{ route('pharmacy.stock.in.store') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="medicine_id" value="{{ $medicine->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" min="1" required>
                                    @error('quantity')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Batch No</label>
                                    <input type="text" name="batch_no" value="{{ old('batch_no') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('batch_no')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('expiry_date')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">MRP</label>
                                    <input type="number" name="mrp" value="{{ old('mrp') }}" step="0.01" min="0" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('mrp')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sale Price</label>
                                    <input type="number" name="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('sale_price')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Purchase Price</label>
                                    <input type="number" name="purchase_price" value="{{ old('purchase_price') }}" step="0.01" min="0" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('purchase_price')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                                    <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                    @error('notes')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded">
                                    Add Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
