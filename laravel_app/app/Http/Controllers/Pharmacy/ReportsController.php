<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\PharmacyInvoice;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    use ResolvesPharmacyStore;

    public function stock()
    {
        $store = $this->resolveStore();

        $agg = StockBatch::query()
            ->select('medicine_id')
            ->selectRaw('SUM(quantity_on_hand) as on_hand')
            ->selectRaw('SUM(COALESCE(purchase_price, 0) * quantity_on_hand) as stock_value')
            ->where('pharmacy_store_id', $store->id)
            ->groupBy('medicine_id');

        $items = Medicine::query()
            ->leftJoinSub($agg, 'agg', function ($join) {
                $join->on('medicines.id', '=', 'agg.medicine_id');
            })
            ->select([
                'medicines.*',
                DB::raw('COALESCE(agg.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(agg.stock_value, 0) as stock_value'),
            ])
            ->orderByDesc('stock_value')
            ->limit(200)
            ->get();

        $totalValue = StockBatch::where('pharmacy_store_id', $store->id)
            ->selectRaw('SUM(COALESCE(purchase_price, 0) * quantity_on_hand) as v')
            ->value('v') ?? 0;

        return view('pharmacy.reports.stock', compact('store', 'items', 'totalValue'));
    }

    public function nearExpiry()
    {
        $store = $this->resolveStore();
        $cutoff = Carbon::now()->addDays($store->near_expiry_days)->toDateString();

        $batches = StockBatch::where('pharmacy_store_id', $store->id)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoff)
            ->where('quantity_on_hand', '>', 0)
            ->with('medicine')
            ->orderBy('expiry_date')
            ->paginate(50);

        return view('pharmacy.reports.near_expiry', compact('store', 'batches', 'cutoff'));
    }

    public function inventoryOverview()
    {
        $store = $this->resolveStore();

        $manufacturerSql = "COALESCE(NULLIF(manufacturers.name, ''), NULLIF(medicines.manufacturer_raw, ''), '—')";
        $categorySql = "COALESCE(NULLIF(medicines.therapeutic_class, ''), NULLIF(medicines.type, ''), 'Uncategorized')";
        $dosageFormSql = "COALESCE(NULLIF(dosage_forms.name, ''), '—')";
        $manufacturerExpr = DB::raw($manufacturerSql);
        $categoryExpr = DB::raw($categorySql);
        $dosageFormExpr = DB::raw($dosageFormSql);

        $base = StockBatch::query()
            ->join('medicines', 'medicines.id', '=', 'stock_batches.medicine_id')
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'medicines.manufacturer_id')
            ->leftJoin('dosage_forms', 'dosage_forms.id', '=', 'medicines.dosage_form_id')
            ->where('stock_batches.pharmacy_store_id', $store->id)
            ->where('stock_batches.quantity_on_hand', '>', 0);

        $byManufacturer = (clone $base)
            ->selectRaw($manufacturerSql . ' as label')
            ->selectRaw('COUNT(DISTINCT stock_batches.medicine_id) as medicines')
            ->selectRaw('SUM(stock_batches.quantity_on_hand) as units')
            ->groupBy($manufacturerExpr)
            ->orderByDesc('medicines')
            ->limit(80)
            ->get();

        $byCategory = (clone $base)
            ->selectRaw($categorySql . ' as label')
            ->selectRaw('COUNT(DISTINCT stock_batches.medicine_id) as medicines')
            ->selectRaw('SUM(stock_batches.quantity_on_hand) as units')
            ->groupBy($categoryExpr)
            ->orderByDesc('medicines')
            ->limit(80)
            ->get();

        $byDosageForm = (clone $base)
            ->selectRaw($dosageFormSql . ' as label')
            ->selectRaw('COUNT(DISTINCT stock_batches.medicine_id) as medicines')
            ->selectRaw('SUM(stock_batches.quantity_on_hand) as units')
            ->groupBy($dosageFormExpr)
            ->orderByDesc('medicines')
            ->limit(80)
            ->get();

        $totals = (clone $base)
            ->selectRaw('COUNT(DISTINCT stock_batches.medicine_id) as medicines')
            ->selectRaw('SUM(stock_batches.quantity_on_hand) as units')
            ->first();

        return view('pharmacy.reports.inventory_overview', compact('store', 'byManufacturer', 'byCategory', 'byDosageForm', 'totals'));
    }

    public function sales()
    {
        $store = $this->resolveStore();

        $rows = PharmacyInvoice::where('pharmacy_store_id', $store->id)
            ->selectRaw('DATE(issued_at) as day')
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('SUM(total) as total_sales')
            ->selectRaw('SUM(paid_total) as total_paid')
            ->whereNotNull('issued_at')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(60)
            ->get();

        return view('pharmacy.reports.sales', compact('store', 'rows'));
    }

    public function movements()
    {
        $store = $this->resolveStore();

        $movements = StockMovement::where('pharmacy_store_id', $store->id)
            ->with('medicine')
            ->orderByDesc('id')
            ->paginate(50);

        return view('pharmacy.reports.movements', compact('store', 'movements'));
    }

    public function audit()
    {
        $store = $this->resolveStore();

        $logs = AuditLog::where('pharmacy_store_id', $store->id)
            ->with('user')
            ->orderByDesc('id')
            ->paginate(50);

        return view('pharmacy.reports.audit', compact('store', 'logs'));
    }
}
