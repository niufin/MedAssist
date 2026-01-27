<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pharmacy Settings') }}
            </h2>
            <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pharmacy.settings.update') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ old('name', $store->name) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number', $store->contact_number) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $store->low_stock_threshold) }}" min="0" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Near Expiry Days</label>
                                <input type="number" name="near_expiry_days" value="{{ old('near_expiry_days', $store->near_expiry_days) }}" min="1" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <input type="text" name="address" value="{{ old('address', $store->address) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

