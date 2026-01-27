<x-app-layout>
    @section('title', 'Pharmacy Medicines - MedAssist')
    
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pharmacy Medicines') }}
            </h2>
            <div class="flex items-center gap-2">
                @can('isAdmin')
                    <a href="{{ route('pharmacy.medicines.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add Medicine
                    </a>
                @endcan
                @can('isSuperAdmin')
                    <form action="{{ route('pharmacy.medicines.clear') }}" method="POST" onsubmit="return confirm('This will remove ALL medicines and related stock. Continue?');">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Remove All
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <form method="GET" action="{{ route('pharmacy.medicines.index') }}" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Search brand, ingredient, manufacturer..." class="w-full sm:w-96 border-gray-300 rounded shadow-sm">
                        <select name="search_by" class="w-full sm:w-64 border-gray-300 rounded shadow-sm">
                            <option value="all" {{ ($searchBy ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="brand" {{ ($searchBy ?? 'all') === 'brand' ? 'selected' : '' }}>Brand name</option>
                            <option value="name" {{ ($searchBy ?? 'all') === 'name' ? 'selected' : '' }}>Medicine name</option>
                            <option value="composition" {{ ($searchBy ?? 'all') === 'composition' ? 'selected' : '' }}>Composition / ingredients</option>
                            <option value="strength" {{ ($searchBy ?? 'all') === 'strength' ? 'selected' : '' }}>Strength</option>
                            <option value="manufacturer" {{ ($searchBy ?? 'all') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                            <option value="class" {{ ($searchBy ?? 'all') === 'class' ? 'selected' : '' }}>Therapeutic class</option>
                        </select>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="show_discontinued" value="1" class="rounded border-gray-300" {{ $showDiscontinued ? 'checked' : '' }}>
                            Show discontinued
                        </label>
                        <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">
                            Search
                        </button>
                        @if($search !== '')
                            <a href="{{ route('pharmacy.medicines.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-center">
                                Clear
                            </a>
                        @endif
                    </form>

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Composition</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pack / Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    @can('isAdmin')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($medicines as $m)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $m->name }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @php
                                                $gd = $m->generic_display;
                                                $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
                                            @endphp
                                            <div class="text-sm">
                                                {{ $compText ?: (($m->primary_ingredient ? ($m->primary_ingredient . ($m->primary_strength ? ' ' . $m->primary_strength : '')) : '—')) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $m->type ?? '—' }}{{ $m->strength ? ' • ' . $m->strength : '' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                            {{ $m->manufacturer?->name ?? $m->manufacturer_raw ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                            @php
                                                $p = $m->packages->first();
                                            @endphp
                                            @if($p)
                                                <div class="text-sm">
                                                    {{ $p->pack_type ? ($p->pack_type . ' ') : '' }}{{ $p->pack_size_value ?? '' }}{{ $p->pack_size_unit ? ' ' . $p->pack_size_unit : '' }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ₹{{ number_format($p->price_inr ?? $p->mrp ?? 0, 2) }}
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $m->therapeutic_class ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($m->is_discontinued)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Discontinued</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $m->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                    {{ $m->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            @endif
                                        </td>
                                        @can('isAdmin')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('pharmacy.medicines.edit', $m->id) }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition">
                                                        Edit
                                                    </a>
                                                    <form action="{{ route('pharmacy.medicines.destroy', $m->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this medicine?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="7">No medicines found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $medicines->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
