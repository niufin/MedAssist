<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="role" name="role">
                                <option value="doctor" {{ $user->role == 'doctor' ? 'selected' : '' }}>Doctor</option>
                                <option value="pharmacist" {{ $user->role == 'pharmacist' ? 'selected' : '' }}>Pharmacist</option>
                                <option value="lab_assistant" {{ $user->role == 'lab_assistant' ? 'selected' : '' }}>Lab Assistant</option>
                                <option value="patient" {{ $user->role == 'patient' ? 'selected' : '' }}>Patient</option>
                                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="hospital_admin" {{ $user->role == 'hospital_admin' ? 'selected' : '' }}>Hospital Admin</option>
                                @endif
                                @if(auth()->user()->isSuperAdmin())
                                <option value="super_admin" {{ $user->role == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                @endif
                            </select>
                        </div>

                        <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Mobile Number</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="contact_number" type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="medical_center_name">Medical Center</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="medical_center_name" type="text" name="medical_center_name" value="{{ old('medical_center_name', $user->medical_center_name) }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="degrees">Degrees / Qualification</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="degrees" type="text" name="degrees" value="{{ old('degrees', $user->degrees) }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="license_number">License Number</label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="license_number" type="text" name="license_number" value="{{ old('license_number', $user->license_number) }}">
                            </div>
                        </div>

                        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="hospital_admin_id">Assign to Hospital Admin</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="hospital_admin_id" name="hospital_admin_id">
                                <option value="">None</option>
                                @foreach($hospitalAdmins as $ha)
                                <option value="{{ $ha->id }}" {{ $user->hospital_admin_id == $ha->id ? 'selected' : '' }}>{{ $ha->name }} (ID: {{ $ha->id }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Assign Doctors, Pharmacists, Lab Assistants, or Patients to a Hospital Admin.</p>
                        </div>
                        @endif
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Status</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="status" name="status">
                                <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending" {{ $user->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="suspended" {{ $user->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full sm:w-auto" type="submit">
                                Update User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 w-full sm:w-auto text-center sm:text-right">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
