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
