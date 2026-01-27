<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Sales Invoices') }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }}
                </div>
            </div>
            <a href="{{ route('pharmacy.dispense.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Dispense
            </a>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($invoices as $inv)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('pharmacy.invoices.show', $inv->id) }}" class="font-bold text-blue-700 hover:text-blue-900">
                                                {{ $inv->invoice_no }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $inv->patient?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ number_format((float) $inv->total, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ number_format((float) $inv->paid_total, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $inv->status === 'paid' ? 'bg-green-100 text-green-800' : ($inv->status === 'refunded' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($inv->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $inv->issued_at ? $inv->issued_at->format('d M Y, h:i A') : $inv->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-6 text-gray-500" colspan="6">No invoices yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

