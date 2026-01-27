<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Purchase Orders') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.suppliers.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Suppliers
                </a>
                <a href="{{ route('pharmacy.purchases.orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    New PO
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($orders as $o)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('pharmacy.purchases.orders.show', $o->id) }}" class="font-bold text-blue-700 hover:text-blue-900">
                                                {{ $o->po_no }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $o->supplier?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $o->status === 'received' ? 'bg-green-100 text-green-800' : ($o->status === 'ordered' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700') }}">
                                                {{ ucfirst($o->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $o->ordered_at ? $o->ordered_at->format('d M Y, h:i A') : $o->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="4">No purchase orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

