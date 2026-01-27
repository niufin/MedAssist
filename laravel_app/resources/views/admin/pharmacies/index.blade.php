<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pharmacies') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <a href="{{ route('admin.pharmacies.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add Pharmacy
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pharmacy</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hospital</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Low Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Near Expiry (days)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($stores as $s)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $s->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $s->hospitalAdmin?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $s->contact_number ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $s->low_stock_threshold }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $s->near_expiry_days }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('admin.pharmacies.edit', $s->id) }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center gap-2 shadow-sm">
                                                    <i class="fa-solid fa-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.pharmacies.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this pharmacy?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center gap-2 shadow-sm">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-6 text-sm text-gray-500">No pharmacies found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

