<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Hospital') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.hospitals.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Hospital Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded" required>
                            @error('name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full border-gray-300 rounded" required>
                            @error('email')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Contact Number</label>
                            <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full border-gray-300 rounded">
                            @error('contact_number')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Clinic Address</label>
                            <input type="text" name="clinic_address" value="{{ old('clinic_address') }}" class="w-full border-gray-300 rounded">
                            @error('clinic_address')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Clinic Contact Number</label>
                            <input type="text" name="clinic_contact_number" value="{{ old('clinic_contact_number') }}" class="w-full border-gray-300 rounded">
                            @error('clinic_contact_number')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Clinic Email</label>
                            <input type="email" name="clinic_email" value="{{ old('clinic_email') }}" class="w-full border-gray-300 rounded">
                            @error('clinic_email')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Clinic Certificate / Registration Number</label>
                            <input type="text" name="clinic_registration_number" value="{{ old('clinic_registration_number') }}" class="w-full border-gray-300 rounded">
                            @error('clinic_registration_number')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Clinic GSTIN</label>
                            <input type="text" name="clinic_gstin" value="{{ old('clinic_gstin') }}" class="w-full border-gray-300 rounded">
                            @error('clinic_gstin')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" class="w-full border-gray-300 rounded" required>
                            @error('password')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded" required>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Hospital
                            </button>
                            <a href="{{ route('admin.hospitals.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
