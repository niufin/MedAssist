<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('admin.users.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add New User
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $assignableRoles = ['doctor','pharmacist','lab_assistant','patient'];
                                        @endphp
                                        @if(in_array($user->role, $assignableRoles))
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="name" value="{{ $user->name }}">
                                                    <input type="hidden" name="email" value="{{ $user->email }}">
                                                    <input type="hidden" name="role" value="{{ $user->role }}">
                                                    <input type="hidden" name="status" value="{{ $user->status }}">
                                                    <select name="hospital_admin_id" class="text-xs border rounded px-2 py-1 bg-white">
                                                        <option value="">None</option>
                                                        @foreach($hospitalAdmins as $ha)
                                                            <option value="{{ $ha->id }}" @if($user->hospital_admin_id == $ha->id) selected @endif>{{ $ha->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1 rounded text-[11px] font-bold uppercase transition">
                                                        Assign
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-3">
                                            @if($user->status !== 'active')
                                            <form action="{{ route('admin.users.approve', $user->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-green-100 text-green-700 hover:bg-green-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center gap-2 shadow-sm">
                                                    <i class="fa-solid fa-check"></i> Approve
                                                </button>
                                            </form>
                                            @endif
                                            
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center gap-2 shadow-sm">
                                                <i class="fa-solid fa-edit"></i> Edit
                                            </a>

                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center gap-2 shadow-sm">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
