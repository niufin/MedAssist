<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create User') }}
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
                                <option value="doctor">Doctor</option>
                                <option value="pharmacist">Pharmacist</option>
                                <option value="lab_assistant">Lab Assistant</option>
                                <option value="patient">Patient</option>
                                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                <option value="admin">Admin</option>
                                <option value="hospital_admin">Hospital Admin</option>
                                @endif
                            </select>
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
