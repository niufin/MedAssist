<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Adjust Stock') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $batch->medicine->name }} @if($batch->medicine->strength) ({{ $batch->medicine->strength }}) @endif
                </div>
            </div>
            <a href="{{ route('pharmacy.inventory.show', $batch->medicine_id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 border rounded p-3">
                            <div class="text-xs text-gray-500 font-bold uppercase">Batch</div>
                            <div class="font-bold text-gray-800">{{ $batch->batch_no ?? '—' }}</div>
                        </div>
                        <div class="bg-gray-50 border rounded p-3">
                            <div class="text-xs text-gray-500 font-bold uppercase">Expiry</div>
                            <div class="font-bold text-gray-800">{{ $batch->expiry_date ? $batch->expiry_date->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="bg-gray-50 border rounded p-3">
                            <div class="text-xs text-gray-500 font-bold uppercase">On Hand</div>
                            <div class="font-bold text-gray-800">{{ $batch->quantity_on_hand }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('pharmacy.stock.adjust.update', $batch->id) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adjustment (delta)</label>
                            <input type="number" name="delta" value="{{ old('delta') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                            <div class="text-xs text-gray-500 mt-1">Use positive for add, negative for remove.</div>
                            @error('delta')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            @error('notes')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded">
                                Save Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

