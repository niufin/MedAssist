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
            <div class="flex items-center gap-2">
                <a href="{{ route('pharmacy.invoices.pdf', $invoice->id) }}" class="no-loader bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    PDF
                </a>
                <a href="{{ route('pharmacy.invoices.print', $invoice->id) }}" class="no-loader bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Print
                </a>
                <a href="{{ route('pharmacy.invoices.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Total</div>
                        <div class="font-bold text-gray-900">{{ number_format((float) $invoice->total, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Paid</div>
                        <div class="font-bold text-gray-900">{{ number_format((float) $invoice->paid_total, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 font-bold uppercase">Outstanding</div>
                        <div class="font-bold text-gray-900">{{ number_format((float) $outstanding, 2) }}</div>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $dispensedLabel ?: $it->medicine_name }}</div>
                                            @if($dispensedLabel && $it->medicine_name && $it->medicine_name !== $dispensedLabel)
                                                <div class="text-xs text-gray-500">Rx: {{ $it->medicine_name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $it->quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ number_format((float) $it->unit_price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ number_format((float) $it->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="font-bold text-gray-800 mb-3">Record Payment</div>
                        <form method="POST" action="{{ route('pharmacy.invoices.payment', $invoice->id) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Method</label>
                                    <select name="method" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reference (optional)</label>
                                <input type="text" name="reference" value="{{ old('reference') }}" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                            </div>
                            <div class="flex items-center justify-end">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                                    Add Payment
                                </button>
                            </div>
                        </form>

                        <div class="mt-6">
                            <div class="font-bold text-gray-800 mb-2">Payments</div>
                            <div class="space-y-2">
                                @forelse($invoice->payments as $p)
                                    <div class="border rounded p-3 flex items-center justify-between">
                                        <div class="text-sm text-gray-700">
                                            {{ strtoupper($p->method) }} • {{ $p->paid_at ? $p->paid_at->format('d M Y, h:i A') : $p->created_at->format('d M Y, h:i A') }}
                                            @if($p->reference)
                                                <span class="text-gray-500">• {{ $p->reference }}</span>
                                            @endif
                                        </div>
                                        <div class="font-bold text-gray-900">{{ number_format((float) $p->amount, 2) }}</div>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500">No payments yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="font-bold text-gray-800 mb-3">Process Return</div>
                        <form method="POST" action="{{ route('pharmacy.invoices.return', $invoice->id) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Invoice Item</label>
                                <select name="invoice_item_id" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                                    <option value="">Select item</option>
                                    @foreach($invoice->items as $it)
                                        <option value="{{ $it->id }}">{{ $it->medicine_name }} (Qty: {{ $it->quantity }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" class="mt-1 w-full border-gray-300 rounded shadow-sm" required>
                            </div>
                            <div class="flex items-center justify-end">
                                <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded">
                                    Process Return
                                </button>
                            </div>
                        </form>

                        <div class="mt-6">
                            <div class="font-bold text-gray-800 mb-2">Returns</div>
                            <div class="space-y-2">
                                @forelse($invoice->returns as $r)
                                    <div class="border rounded p-3 flex items-center justify-between">
                                        <div class="text-sm text-gray-700">
                                            {{ $r->created_at->format('d M Y, h:i A') }}
                                        </div>
                                        <div class="font-bold text-gray-900">{{ number_format((float) $r->refund_total, 2) }}</div>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500">No returns yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
