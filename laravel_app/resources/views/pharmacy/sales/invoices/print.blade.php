<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_no }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .page { max-width: 800px; margin: 0 auto; padding: 16px; }
        .row { display: flex; justify-content: space-between; gap: 16px; }
        .muted { color: #6b7280; }
        .h1 { font-size: 18px; font-weight: 700; }
        .h2 { font-size: 13px; font-weight: 700; margin: 0 0 8px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
        .grid4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .k { font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; font-weight: 700; }
        .v { font-weight: 700; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; vertical-align: top; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .right { text-align: right; }
        .totals { margin-top: 10px; width: 100%; }
        .totals td { border: none; padding: 4px 0; }
        .btnbar { display: flex; gap: 8px; justify-content: flex-end; }
        .btn { display: inline-block; padding: 8px 10px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; color: #111827; text-decoration: none; font-weight: 700; font-size: 12px; }
        .btn.primary { background: #111827; color: #fff; border-color: #111827; }
        @media print { .no-print { display: none !important; } .page { padding: 0; } }
    </style>
</head>
<body>
    <div class="page">
        @if(($mode ?? 'print') !== 'pdf')
            <div class="row no-print" style="margin-bottom: 12px;">
                <div></div>
                <div class="btnbar">
                    <a class="btn" href="{{ route('pharmacy.invoices.show', $invoice->id) }}">Back</a>
                    <a class="btn no-loader" href="{{ route('pharmacy.invoices.pdf', $invoice->id) }}">Download PDF</a>
                    <button class="btn primary" onclick="window.print()">Print</button>
                </div>
            </div>
        @endif

        <div class="row" style="align-items: flex-start;">
            <div>
                <div class="h1">{{ $store->name }}</div>
                <div class="muted" style="margin-top: 2px;">Sales Invoice</div>
            </div>
            <div style="text-align: right;">
                <div class="h1">{{ $invoice->invoice_no }}</div>
                <div class="muted" style="margin-top: 2px;">
                    {{ ($invoice->issued_at ?? $invoice->created_at)->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 12px;">
            <div class="card" style="flex: 1;">
                <div class="h2">Bill To</div>
                <div style="font-weight: 700;">{{ $invoice->patient?->name ?? 'Walk-in' }}</div>
                <div class="muted" style="margin-top: 2px;">
                    MRN: {{ $invoice->patient?->mrn ?? '—' }}
                </div>
            </div>
            <div class="card" style="flex: 1;">
                <div class="h2">Summary</div>
                <div class="grid4">
                    <div>
                        <div class="k">Total</div>
                        <div class="v">₹{{ number_format((float) $invoice->total, 2) }}</div>
                    </div>
                    <div>
                        <div class="k">Paid</div>
                        <div class="v">₹{{ number_format((float) $invoice->paid_total, 2) }}</div>
                    </div>
                    <div>
                        <div class="k">Outstanding</div>
                        <div class="v">₹{{ number_format((float) $outstanding, 2) }}</div>
                    </div>
                    <div>
                        <div class="k">Status</div>
                        <div class="v">{{ ucfirst($invoice->status) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 12px;">
            <div class="h2">Items</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 52%;">Medicine</th>
                        <th style="width: 12%;" class="right">Qty</th>
                        <th style="width: 18%;" class="right">Unit</th>
                        <th style="width: 18%;" class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $it)
                        @php
                            $dispensedLabel = null;
                            if ($it->medicine) {
                                $base = $it->medicine->brand_name ?: $it->medicine->name;
                                $dispensedLabel = trim($base . ' ' . ($it->medicine->strength ?: ''));
                            }
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight: 700;">{{ $dispensedLabel ?: $it->medicine_name }}</div>
                                @if($dispensedLabel && $it->medicine_name && $it->medicine_name !== $dispensedLabel)
                                    <div class="muted" style="margin-top: 2px;">Rx: {{ $it->medicine_name }}</div>
                                @endif
                            </td>
                            <td class="right">{{ (int) $it->quantity }}</td>
                            <td class="right">₹{{ number_format((float) $it->unit_price, 2) }}</td>
                            <td class="right" style="font-weight: 700;">₹{{ number_format((float) $it->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td class="muted">Subtotal</td>
                    <td class="right">₹{{ number_format((float) $invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="muted">Discount</td>
                    <td class="right">₹{{ number_format((float) $invoice->discount, 2) }}</td>
                </tr>
                <tr>
                    <td class="muted">Tax</td>
                    <td class="right">₹{{ number_format((float) $invoice->tax, 2) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Total</td>
                    <td class="right" style="font-weight: 700;">₹{{ number_format((float) $invoice->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="row" style="margin-top: 12px;">
            <div class="card" style="flex: 1;">
                <div class="h2">Payments</div>
                @if($invoice->payments->isEmpty())
                    <div class="muted">No payments yet.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Date</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $p)
                                <tr>
                                    <td>{{ strtoupper($p->method) }}</td>
                                    <td>{{ ($p->paid_at ?? $p->created_at)->format('d M Y, h:i A') }}</td>
                                    <td class="right">₹{{ number_format((float) $p->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="card" style="flex: 1;">
                <div class="h2">Returns</div>
                @if($invoice->returns->isEmpty())
                    <div class="muted">No returns yet.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="right">Refund</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->returns as $r)
                                <tr>
                                    <td>{{ $r->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="right">₹{{ number_format((float) $r->refund_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="muted" style="margin-top: 14px; text-align: center;">
            Generated by {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
