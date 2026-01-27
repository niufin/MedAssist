<x-app-layout>
    <x-slot name="header">
        @php
            $roleKey = strtolower(trim((string) ($role ?? request()->query('role', ''))));
            $title = $roleKey !== '' ? ('Create ' . ucwords(str_replace('_', ' ', $roleKey))) : 'Create User';
        @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __($title) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="name" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" name="password" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">Confirm Password</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password_confirmation" type="password" name="password_confirmation" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="role" name="role">
                                <option value="doctor" {{ ($roleKey ?? '') === 'doctor' ? 'selected' : '' }}>Doctor</option>
                                <option value="pharmacist" {{ ($roleKey ?? '') === 'pharmacist' ? 'selected' : '' }}>Pharmacist</option>
                                <option value="lab_assistant" {{ ($roleKey ?? '') === 'lab_assistant' ? 'selected' : '' }}>Lab Assistant</option>
                                <option value="patient" {{ ($roleKey ?? '') === 'patient' ? 'selected' : '' }}>Patient</option>
                                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                <option value="admin" {{ ($roleKey ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="hospital_admin" {{ ($roleKey ?? '') === 'hospital_admin' ? 'selected' : '' }}>Hospital Admin</option>
                                @endif
                            </select>
                        </div>

                        <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Mobile Number</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="contact_number" type="text" name="contact_number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="medical_center_name">Medical Center</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="medical_center_name" type="text" name="medical_center_name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="degrees">Degrees / Qualification</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="degrees" type="text" name="degrees">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="designation">Designation</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="designation" type="text" name="designation">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="additional_qualifications">Additional Courses / Diplomas</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="additional_qualifications" type="text" name="additional_qualifications">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="license_number">License Number</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="license_number" type="text" name="license_number">
                            </div>
                        </div>
                        
                        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="hospital_admin_id">Assign to Hospital Admin</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="hospital_admin_id" name="hospital_admin_id">
                                <option value="">None</option>
                                @foreach($hospitalAdmins as $ha)
                                <option value="{{ $ha->id }}">{{ $ha->name }} (ID: {{ $ha->id }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Assign Doctors, Pharmacists, Lab Assistants, or Patients to a Hospital Admin.</p>
                        </div>
                        @endif
                        
                        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 w-full sm:w-auto text-center sm:text-left">Cancel</a>
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full sm:w-auto" type="submit">
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
