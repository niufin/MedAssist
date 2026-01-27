<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Supplier') }}
            </h2>
            <a href="{{ route('pharmacy.suppliers.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pharmacy.suppliers.update', $supplier->id) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                                <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number', $supplier->contact_number) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">GSTIN</label>
                                <input type="text" name="gstin" value="{{ old('gstin', $supplier->gstin) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <input type="text" name="address" value="{{ old('address', $supplier->address) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" class="border-gray-300 rounded" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="text-sm text-gray-700">Active</label>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

