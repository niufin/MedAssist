<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pharmacy\Concerns\ResolvesPharmacyStore;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use ResolvesPharmacyStore;

    public function index(Request $request)
    {
        $store = $this->resolveStore();
        $search = trim((string) $request->query('q', ''));
        $searchBy = trim((string) $request->query('search_by', 'all'));

        $nearExpiryDate = Carbon::now()->addDays($store->near_expiry_days)->toDateString();

        $agg = StockBatch::query()
            ->select('medicine_id')
            ->selectRaw('SUM(quantity_on_hand) as on_hand')
            ->selectRaw('SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= ? THEN quantity_on_hand ELSE 0 END) as near_expiry_on_hand', [$nearExpiryDate])
            ->selectRaw('MAX(sale_price) as price')
            ->where('pharmacy_store_id', $store->id)
            ->where('quantity_on_hand', '>', 0)
            ->groupBy('medicine_id');

        $query = Medicine::query()
            ->joinSub($agg, 'agg', function ($join) {
                $join->on('medicines.id', '=', 'agg.medicine_id');
            })
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'medicines.manufacturer_id')
            ->with(['stockBatches' => function ($q) use ($store) {
                $q->where('pharmacy_store_id', $store->id)
                  ->where('quantity_on_hand', '>', 0)
                  ->orderBy('expiry_date');
            }])
            ->select([
                'medicines.*',
                'manufacturers.name as manufacturer_name',
                DB::raw('COALESCE(agg.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(agg.near_expiry_on_hand, 0) as near_expiry_on_hand'),
                DB::raw('COALESCE(agg.price, 0) as price'),
            ])
            ->orderBy('medicines.name')
            ->orderBy('medicines.strength');

        if ($search !== '') {
            $query->search($search, $searchBy);
        }

        $items = $query->paginate(50)->withQueryString();

        return view('pharmacy.inventory.index', compact('store', 'items', 'search', 'searchBy'));
    }

    public function show(Medicine $medicine)
    {
        $store = $this->resolveStore();

        $batches = StockBatch::where('pharmacy_store_id', $store->id)
            ->where('medicine_id', $medicine->id)
            ->orderByRaw('expiry_date IS NULL, expiry_date asc')
            ->get();

        $movements = StockMovement::where('pharmacy_store_id', $store->id)
            ->where('medicine_id', $medicine->id)
            ->latest()
            ->limit(100)
            ->get();

        return view('pharmacy.inventory.show', compact('store', 'medicine', 'batches', 'movements'));
    }
}
