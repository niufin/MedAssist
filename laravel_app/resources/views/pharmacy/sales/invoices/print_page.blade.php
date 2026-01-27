<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $invoice->invoice_no }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $store->name }} • {{ $invoice->patient?->name ?? 'Walk-in' }}
                </div>
            </div>
            <div class="flex items-center gap-2 no-print">
                <a href="{{ route('pharmacy.invoices.pdf', $invoice->id) }}" class="no-loader bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    PDF
                </a>
                <button type="button" onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Print
                </button>
                <a href="{{ route('pharmacy.invoices.show', $invoice->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Back
                </a>
            </div>
        </div>
        <style>
            @media print {
                header { display: none !important; }
                .no-print { display: none !important; }
                body { background: #fff !important; }
            }
        </style>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Total</div>
                        <div class="font-bold text-gray-900">₹{{ number_format((float) $invoice->total, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Paid</div>
                        <div class="font-bold text-gray-900">₹{{ number_format((float) $invoice->paid_total, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Outstanding</div>
                        <div class="font-bold text-gray-900">₹{{ number_format((float) $outstanding, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Status</div>
                        <div class="font-bold text-gray-900">{{ ucfirst($invoice->status) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="font-bold text-gray-800 mb-3">Items</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->items as $it)
                                    @php
                                        $dispensedLabel = null;
                                        if ($it->medicine) {
                                            $base = $it->medicine->brand_name ?: $it->medicine->name;
                                            $dispensedLabel = trim($base . ' ' . ($it->medicine->strength ?: ''));
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $dispensedLabel ?: $it->medicine_name }}</div>
                                            @if($dispensedLabel && $it->medicine_name && $it->medicine_name !== $dispensedLabel)
                                                <div class="text-xs text-gray-500">Rx: {{ $it->medicine_name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-700">{{ (int) $it->quantity }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700">₹{{ number_format((float) $it->unit_price, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">₹{{ number_format((float) $it->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <div class="w-full sm:w-80 space-y-1">
                            <div class="flex justify-between text-sm text-gray-700">
                                <span>Subtotal</span>
                                <span>₹{{ number_format((float) $invoice->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700">
                                <span>Discount</span>
                                <span>₹{{ number_format((float) $invoice->discount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700">
                                <span>Tax</span>
                                <span>₹{{ number_format((float) $invoice->tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-gray-900 border-t pt-2">
                                <span>Total</span>
                                <span>₹{{ number_format((float) $invoice->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

