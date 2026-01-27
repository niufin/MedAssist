<?php

namespace App\Http\Controllers\Pharmacy;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\PharmacyInvoice;
use App\Models\PharmacyInvoiceItem;
use App\Models\PharmacyPayment;
use App\Models\PharmacyReturn;
use App\Models\PharmacyReturnItem;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    use ResolvesPharmacyStore;

    public function index(Request $request)
    {
        $store = $this->resolveStore();

        $invoices = PharmacyInvoice::where('pharmacy_store_id', $store->id)
            ->with('patient')
            ->orderByDesc('id')
            ->paginate(25);

        return view('pharmacy.sales.invoices.index', compact('store', 'invoices'));
    }

    public function show(PharmacyInvoice $invoice)
    {
        $store = $this->resolveStore();
        if ((int) $invoice->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $invoice->load(['items.medicine', 'payments', 'returns', 'patient']);
        $outstanding = max(0, (float) $invoice->total - (float) $invoice->paid_total);

        return view('pharmacy.sales.invoices.show', compact('store', 'invoice', 'outstanding'));
    }

    public function print(PharmacyInvoice $invoice)
    {
        $store = $this->resolveStore();
        if ((int) $invoice->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $invoice->load(['items.medicine', 'payments', 'returns', 'patient', 'store']);
        $outstanding = max(0, (float) $invoice->total - (float) $invoice->paid_total);
        return view('pharmacy.sales.invoices.print_page', compact('store', 'invoice', 'outstanding'));
    }

    public function pdf(PharmacyInvoice $invoice)
    {
        $store = $this->resolveStore();
        if ((int) $invoice->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $invoice->load(['items.medicine', 'payments', 'returns', 'patient', 'store']);
        $outstanding = max(0, (float) $invoice->total - (float) $invoice->paid_total);
        $mode = 'pdf';

        $pdf = Pdf::loadView('pharmacy.sales.invoices.print', compact('store', 'invoice', 'outstanding', 'mode'))
            ->setPaper('A4', 'portrait');

        $base = $invoice->invoice_no ?: ('invoice_' . $invoice->id);
        $name = 'Invoice_' . (Str::slug($base) ?: ('invoice_' . $invoice->id)) . '.pdf';

        return $pdf->download($name);
    }

    public function addPayment(Request $request, PharmacyInvoice $invoice)
    {
        $store = $this->resolveStore();
        if ((int) $invoice->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        PharmacyPayment::create([
            'pharmacy_invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'paid_at' => Carbon::now(),
            'user_id' => auth()->id(),
        ]);

        $invoice->paid_total = PharmacyPayment::where('pharmacy_invoice_id', $invoice->id)->sum('amount');

        if ((float) $invoice->paid_total >= (float) $invoice->total) {
            $invoice->status = 'paid';
        } elseif ((float) $invoice->paid_total > 0) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'unpaid';
        }

        $invoice->save();

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'invoice_payment',
            'entity_type' => 'pharmacy_invoice',
            'entity_id' => $invoice->id,
            'meta' => [
                'amount' => (float) $validated['amount'],
                'method' => $validated['method'],
            ],
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    public function processReturn(Request $request, PharmacyInvoice $invoice)
    {
        $store = $this->resolveStore();
        if ((int) $invoice->pharmacy_store_id !== (int) $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'invoice_item_id' => ['required', 'exists:pharmacy_invoice_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = PharmacyInvoiceItem::where('pharmacy_invoice_id', $invoice->id)
            ->where('id', $validated['invoice_item_id'])
            ->firstOrFail();

        if ((int) $validated['quantity'] > (int) $item->quantity) {
            return back()->with('error', 'Return quantity exceeds sold quantity.');
        }

        DB::transaction(function () use ($store, $invoice, $item, $validated) {
            $qty = (int) $validated['quantity'];
            $refund = round($qty * (float) $item->unit_price, 2);

            $return = PharmacyReturn::create([
                'pharmacy_invoice_id' => $invoice->id,
                'refund_total' => 0,
                'status' => 'processed',
                'user_id' => auth()->id(),
            ]);

            PharmacyReturnItem::create([
                'pharmacy_return_id' => $return->id,
                'pharmacy_invoice_item_id' => $item->id,
                'quantity' => $qty,
                'refund_amount' => $refund,
            ]);

            $return->refund_total = $refund;
            $return->save();

            if (!empty($item->stock_batch_id)) {
                $batch = StockBatch::lockForUpdate()->find($item->stock_batch_id);
                if ($batch && (int) $batch->pharmacy_store_id === (int) $store->id) {
                    $batch->quantity_on_hand += $qty;
                    $batch->save();

                    StockMovement::create([
                        'pharmacy_store_id' => $store->id,
                        'medicine_id' => $item->medicine_id ?? $batch->medicine_id,
                        'stock_batch_id' => $batch->id,
                        'quantity' => $qty,
                        'movement_type' => 'return_in',
                        'reference_type' => 'pharmacy_return',
                        'reference_id' => $return->id,
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            $totalRefunded = PharmacyReturn::where('pharmacy_invoice_id', $invoice->id)->sum('refund_total');
            if ((float) $totalRefunded >= (float) $invoice->total) {
                $invoice->status = 'refunded';
                $invoice->save();
            }
        });

        AuditLog::create([
            'pharmacy_store_id' => $store->id,
            'user_id' => auth()->id(),
            'action' => 'invoice_return',
            'entity_type' => 'pharmacy_invoice',
            'entity_id' => $invoice->id,
            'meta' => [
                'invoice_item_id' => (int) $validated['invoice_item_id'],
                'quantity' => (int) $validated['quantity'],
            ],
        ]);

        return back()->with('success', 'Return processed.');
    }
}
