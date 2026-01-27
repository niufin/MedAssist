<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            @php
                $roleKey = strtolower(trim((string) ($role ?? '')));
                $roleTitle = $roleKey !== '' ? ucwords(str_replace('_', ' ', $roleKey)) : 'Users';
                $addLabel = $roleKey !== '' ? ('Add ' . ucwords(str_replace('_', ' ', $roleKey))) : 'Add New User';
            @endphp
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage ' . $roleTitle) }}
            </h2>
            <a href="{{ route('admin.users.create', $roleKey !== '' ? ['role' => $roleKey] : []) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                {{ $addLabel }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search name, email, mobile, MRN, license..." class="w-full sm:w-96 border-gray-300 rounded shadow-sm">
                        <select name="role" class="w-full sm:w-64 border-gray-300 rounded shadow-sm">
                            <option value="" {{ ($roleKey ?? '') === '' ? 'selected' : '' }}>All roles</option>
                            <option value="doctor" {{ ($roleKey ?? '') === 'doctor' ? 'selected' : '' }}>Doctors</option>
                            <option value="patient" {{ ($roleKey ?? '') === 'patient' ? 'selected' : '' }}>Patients</option>
                            <option value="pharmacist" {{ ($roleKey ?? '') === 'pharmacist' ? 'selected' : '' }}>Pharmacists</option>
                            <option value="lab_assistant" {{ ($roleKey ?? '') === 'lab_assistant' ? 'selected' : '' }}>Lab Staff</option>
                        </select>
                        <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">
                            Filter
                        </button>
                        @if(($q ?? '') !== '' || ($roleKey ?? '') !== '')
                            <a href="{{ route('admin.users.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-center">
                                Clear
                            </a>
                        @endif
                    </form>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profile</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    @if(!auth()->user()->isHospitalAdmin())
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    @endif
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-700 space-y-1">
                                            @if($user->role === 'doctor')
                                                <div><span class="font-semibold text-gray-900">Degrees:</span> {{ $user->degrees ?? '—' }}</div>
                                                <div><span class="font-semibold text-gray-900">Designation:</span> {{ $user->designation ?? '—' }}</div>
                                                <div><span class="font-semibold text-gray-900">Additional:</span> {{ $user->additional_qualifications ?? '—' }}</div>
                                                <div><span class="font-semibold text-gray-900">License:</span> {{ $user->license_number ?? '—' }}</div>
                                                <div><span class="font-semibold text-gray-900">Mobile:</span> {{ $user->contact_number ?? '—' }}</div>
                                            @elseif($user->role === 'patient')
                                                <div><span class="font-semibold text-gray-900">MRN:</span> {{ $user->mrn ?? '—' }}</div>
                                                <div><span class="font-semibold text-gray-900">Mobile:</span> {{ $user->contact_number ?? '—' }}</div>
                                            @else
                                                <div><span class="font-semibold text-gray-900">Mobile:</span> {{ $user->contact_number ?? '—' }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $st = (string) ($user->status ?? '');
                                            $isActive = $st === 'active';
                                            $label = $isActive ? 'Active' : 'Inactive';
                                            $badge = $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badge }}">
                                            {{ $label }}
                                        </span>
                                        @if($st !== '' && $st !== 'active')
                                            <div class="text-[11px] text-gray-500 mt-1">({{ ucfirst($st) }})</div>
                                        @endif
                                    </td>
                                    @if(!auth()->user()->isHospitalAdmin())
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
                                    @endif
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

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
