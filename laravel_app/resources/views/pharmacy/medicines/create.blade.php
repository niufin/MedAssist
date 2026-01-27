<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Medicine') }}
            </h2>
            <a href="{{ route('pharmacy.medicines.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pharmacy.medicines.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                            @error('name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Brand Name (optional)</label>
                            <input type="text" name="brand_name" value="{{ old('brand_name') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            @error('brand_name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Strength</label>
                                <input type="text" name="strength" value="{{ old('strength') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                @error('strength')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <input type="text" name="type" value="{{ old('type') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                @error('type')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Therapeutic Class</label>
                            <input type="text" name="therapeutic_class" value="{{ old('therapeutic_class') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            @error('therapeutic_class')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" class="border-gray-300 rounded" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label for="is_active" class="text-sm text-gray-700">Active</label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
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
