<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Pharmacy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.pharmacies.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Hospital</label>
                            <select name="hospital_admin_id" class="w-full border-gray-300 rounded" required>
                                <option value="">Select a hospital...</option>
                                @foreach($hospitals as $h)
                                    <option value="{{ $h->id }}" @if((string) old('hospital_admin_id') === (string) $h->id) selected @endif>{{ $h->name }}</option>
                                @endforeach
                            </select>
                            @error('hospital_admin_id')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Pharmacy Name</label>
                            <input type="text" name="name" value="{{ old('name', 'Pharmacy') }}" class="w-full border-gray-300 rounded" required>
                            @error('name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" class="w-full border-gray-300 rounded">
                            @error('address')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Contact Number</label>
                            <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full border-gray-300 rounded">
                            @error('contact_number')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}" class="w-full border-gray-300 rounded" required>
                                @error('low_stock_threshold')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Near Expiry Days</label>
                                <input type="number" name="near_expiry_days" value="{{ old('near_expiry_days', 90) }}" class="w-full border-gray-300 rounded" required>
                                @error('near_expiry_days')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Pharmacy
                            </button>
                            <a href="{{ route('admin.pharmacies.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

