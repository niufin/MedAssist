<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Dispense Prescription') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $consultation->patient_name ?? 'Unknown' }} • MRN: {{ $consultation->patient->mrn ?? 'N/A' }} • {{ $store->name }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.dispense.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Queue
                </a>
                <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Inventory
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4">
                        <div class="font-bold text-gray-800">Prescription</div>
                        <div class="flex items-center gap-2">
                            @if($order)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $order->status === 'dispensed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            @endif

                            @if($order && $order->status === 'open')
                                <form method="POST" action="{{ route('pharmacy.dispense.order.finalize', $order->id) }}">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                                        Finalize
                                    </button>
                                </form>
                            @endif

                            @if($canCreateOrder)
                                <form method="POST" action="{{ route('pharmacy.dispense.order.create', $consultation->id) }}">
                                    @csrf
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Create Dispense Order
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        @if(isset($pData['medicines']) && count($pData['medicines']) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($pData['medicines'] as $m)
                                            @php
                                                $keyName = (string) ($m['name'] ?? ($m['brand_name'] ?? ($m['composition_name'] ?? '')));
                                                $s = $fulfillmentStatuses[$keyName] ?? 'pending';
                                                $brand = trim((string) ($m['brand_name'] ?? ''));
                                                $composition = trim((string) ($m['composition_name'] ?? ($brand === '' ? ($m['name'] ?? '') : '')));
                                                $displayName = $brand !== '' ? $brand : ($composition !== '' ? $composition : $keyName);
                                                $detailParts = [];
                                                if ($brand !== '') {
                                                    $detailComp = trim((string) ($m['brand_composition_text'] ?? ''));
                                                    if ($detailComp === '') {
                                                        $detailComp = $composition;
                                                    }
                                                    if ($detailComp !== '') $detailParts[] = $detailComp;
                                                    $bs = trim((string) ($m['brand_strength'] ?? ''));
                                                    if ($bs !== '') $detailParts[] = $bs;
                                                    $bf = trim((string) ($m['brand_dosage_form'] ?? ''));
                                                    if ($bf !== '') $detailParts[] = $bf;
                                                }
                                            @endphp
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                                    <div>{{ $displayName }}</div>
                                                    @if(!empty($detailParts))
                                                        <div class="text-xs text-gray-500 mt-1">({{ implode(' • ', $detailParts) }})</div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $m['dosage'] ?? '—' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $s === 'given' ? 'bg-green-100 text-green-800' : ($s === 'not_given' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-sm text-gray-500">No medicines found in prescription.</div>
                        @endif
                    </div>
                </div>
            </div>

            @if($order)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="font-bold text-gray-800 mb-4">Dispense Items</div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selection</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($order->items as $item)
                                        @php
                                            $batches = $batchesByMedicineName[$item->medicine_name] ?? collect();
                                            // Extract composition name (remove form prefixes)
                                            $seed = preg_replace('/^\s*(tab|cap|syp|syr|inj|ointment|cream|drop|drops|spray)\.?\s+/i', '', (string) $item->medicine_name);
                                            // Note: We do NOT append dosage here to ensure broader matching in primary_ingredient/generic_display
                                            
                                            $initialBatches = $batches->map(function ($b) {
                                                return [
                                                    'id' => $b->id,
                                                    'batch_no' => $b->batch_no,
                                                    'expiry_date' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : null,
                                                    'quantity_on_hand' => (int) $b->quantity_on_hand,
                                                    'mrp' => $b->mrp,
                                                    'sale_price' => $b->sale_price,
                                                ];
                                            })->values();
                                        @endphp
                                        <tr x-data="dispenseRow({ seed: @js($seed), stockBatchesUrl: @js(route('pharmacy.api.stock_batches')) })">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">{{ $item->medicine_name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $item->dosage ?? '—' }} • {{ $item->frequency ?? '—' }} • {{ $item->duration ?? '—' }} • {{ $item->instruction ?? '—' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                                {{ $item->dispensed_quantity > 0 ? $item->dispensed_quantity : $item->quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                                {{ $item->stock_batch_id ?? '—' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->status === 'dispensed' ? 'bg-green-100 text-green-800' : ($item->status === 'not_given' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-normal text-sm font-medium">
                                                @if($order->status === 'open' && $item->status === 'pending')
                                                    <div class="min-w-64 relative" @click.away="open = false">
                                                        <div class="flex gap-2 items-start">
                                                            <input type="text" 
                                                                   x-model="query" 
                                                                   @input.debounce.500ms="search()" 
                                                                   @focus="openAndLoad()" 
                                                                   @click="openAndLoad()"
                                                                   @keydown.escape="open = false" 
                                                                   placeholder="Search..." 
                                                                   class="w-full border-gray-300 rounded shadow-sm text-sm"
                                                                   autocomplete="off">
                                                            <select x-model="searchBy" class="border-gray-300 rounded shadow-sm text-sm">
                                                                <option value="all">All</option>
                                                                <option value="brand">Brand</option>
                                                                <option value="composition">Composition</option>
                                                                <option value="name">Name</option>
                                                                <option value="strength">Strength</option>
                                                            </select>
                                                            <button type="button" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-3 rounded text-sm" @click="search()">
                                                                Search
                                                            </button>
                                                        </div>
                                                        
                                                        <div x-show="open" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded shadow-lg max-h-72 overflow-auto min-w-[300px]" style="display: none;">
                                                            <div class="px-3 py-2 text-xs text-gray-500" x-show="loading" style="display: none;">Searching...</div>
                                                            <div class="px-3 py-2 text-xs text-gray-500" x-show="!loading && results.length === 0" style="display: none;">No brands found.</div>
                                                            
                                                            <template x-for="item in results" :key="item.type + item.id + item.medicine_id">
                                                                <button type="button" 
                                                                        class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b last:border-b-0 flex justify-between items-center group"
                                                                        :disabled="item.type !== 'batch'"
                                                                        :class="item.type === 'batch' ? '' : 'opacity-60 bg-gray-50 cursor-not-allowed'"
                                                                        @click="selectItem(item)">
                                                                    <div>
                                                                        <div class="text-sm font-semibold text-gray-900" x-text="item.brand_name || item.name"></div>
                                                                        <div class="text-xs text-gray-500">
                                                                            <span x-show="item.composition" x-text="item.composition"></span>
                                                                            <span x-show="item.composition" x-text="' • '"></span>
                                                                            <span x-show="item.strength" x-text="item.strength"></span>
                                                                            <span x-show="item.strength" x-text="' • '"></span>
                                                                            <span x-text="item.type === 'batch' ? 'Batch: ' + item.batch_no : 'Out of stock'"></span>
                                                                            <span x-show="item.expiry_date" x-text="' • Exp: ' + item.expiry_date"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-sm font-bold ml-2" 
                                                                         :class="item.type === 'batch' ? 'text-green-600' : 'text-gray-500'"
                                                                         x-text="item.type === 'batch' ? 'Qty: ' + item.quantity_on_hand : ''">
                                                                    </div>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-500">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if($order->status === 'open' && $item->status === 'pending')
                                                    <div class="flex flex-col sm:flex-row gap-2">
                                                        <form method="POST" action="{{ route('pharmacy.dispense.item.dispense', $item->id) }}" class="flex flex-row gap-2 items-center">
                                                            @csrf
                                                            <input type="number" name="quantity" value="1" min="1" class="w-20 border-gray-300 rounded shadow-sm">
                                                            <input type="hidden" name="medicine_id" :value="selectedMedicineId">
                                                            <input type="hidden" name="stock_batch_id" :value="selectedBatchId">
                                                            
                                                            <button type="submit" :disabled="!selectedBatchId" class="bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-300 text-white font-bold py-2 px-4 rounded">
                                                                Dispense
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('pharmacy.dispense.item.not_given', $item->id) }}">
                                                            @csrf
                                                            <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded">
                                                                Not Given
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-500">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        window.dispenseRow = function (cfg) {
            return {
                open: false,
                seed: cfg.seed || '',
                query: '',
                searchBy: 'all',
                results: [],
                loading: false,
                selectedBatchId: null,
                selectedMedicineId: null,
                stockBatchesUrl: cfg.stockBatchesUrl,

                openAndLoad() {
                    this.open = true;
                    if (!this.loading && this.results.length === 0) {
                        this.search();
                    }
                },

                async search() {
                    const typed = (this.query || '').trim();
                    const fallback = (this.seed || '').trim();
                    const q = typed.length >= 2 ? typed : fallback;
                    if (q.length < 2) {
                        this.results = [];
                        return;
                    }
                    this.loading = true;
                    try {
                        const url = `${this.stockBatchesUrl}?medicine_name=${encodeURIComponent(q)}&search_by=${encodeURIComponent(this.searchBy || 'all')}`;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        this.results = Array.isArray(data.items) ? data.items : [];
                    } catch (e) {
                        console.error(e);
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },
                
                selectItem(item) {
                    if (item.type !== 'batch') return;
                    this.selectedBatchId = item.id;
                    this.selectedMedicineId = item.medicine_id;
                    this.query = `${item.brand_name || item.name} (${item.batch_no})`;
                    this.open = false;
                }
            };
        }
    </script>
</x-app-layout>
