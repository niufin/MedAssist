<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Inventory Overview') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }} • Medicines: {{ (int) ($totals?->medicines ?? 0) }} • Units: {{ (int) ($totals?->units ?? 0) }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Inventory
                </a>
                <a href="{{ route('pharmacy.reports.stock') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Stock Value
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
                        <div class="font-bold text-gray-800 mb-2">Manufacturer Wise</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicines</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($byManufacturer as $row)
                                        @php
                                            $label = (string) ($row->label ?? '—');
                                            $isLink = $label !== '—';
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-3 text-gray-900">
                                                @if($isLink)
                                                    <a class="font-semibold text-blue-700 hover:text-blue-900" href="{{ route('pharmacy.inventory.index', ['search_by' => 'manufacturer', 'q' => $label]) }}">
                                                        {{ $label }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-500">{{ $label }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 font-bold text-gray-900">{{ (int) ($row->medicines ?? 0) }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ (int) ($row->units ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div class="font-bold text-gray-800 mb-2">Medicine Category Wise</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicines</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($byCategory as $row)
                                        @php
                                            $label = (string) ($row->label ?? 'Uncategorized');
                                            $isLink = $label !== 'Uncategorized';
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-3 text-gray-900">
                                                @if($isLink)
                                                    <a class="font-semibold text-blue-700 hover:text-blue-900" href="{{ route('pharmacy.inventory.index', ['search_by' => 'class', 'q' => $label]) }}">
                                                        {{ $label }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-500">{{ $label }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 font-bold text-gray-900">{{ (int) ($row->medicines ?? 0) }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ (int) ($row->units ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div class="font-bold text-gray-800 mb-2">Dosage Form Wise</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage Form</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicines</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($byDosageForm as $row)
                                        <tr>
                                            <td class="px-6 py-3 text-gray-900">{{ (string) ($row->label ?? '—') }}</td>
                                            <td class="px-6 py-3 font-bold text-gray-900">{{ (int) ($row->medicines ?? 0) }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ (int) ($row->units ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

