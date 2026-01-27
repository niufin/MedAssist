<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineApiController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $searchBy = trim((string) $request->query('search_by', 'all'));
        $onlyBrands = (bool) $request->boolean('only_brands', false);
        $composition = trim((string) $request->query('composition', ''));
        $form = $request->query('form');
        $route = $request->query('route');
        $manufacturer = $request->query('manufacturer');
        $schedule = $request->query('schedule');
        $page = (int) ($request->query('page', 0));
        $perPage = (int) ($request->query('per_page', 0));
        $limit = (int) ($request->query('limit', 25));
        if ($limit < 1) $limit = 25;
        if ($limit > 100) $limit = 100;
        if ($page < 0) $page = 0;
        if ($perPage < 0) $perPage = 0;
        if ($perPage > 200) $perPage = 200;

        $query = Medicine::query()
            ->with(['manufacturer', 'dosageForm', 'route', 'ingredients', 'packages'])
            ->where('is_active', true);
        $qEffective = $onlyBrands ? $this->stripDosagePrefix($q) : $q;
        $compositionEffective = $onlyBrands ? $this->stripDosagePrefix($composition) : $composition;
        if ($onlyBrands && $qEffective === '') {
            return response()->json(['items' => []]);
        }
        if ($onlyBrands && strtolower($searchBy) === 'brand' && $compositionEffective !== '') {
            $compositionLike = '%' . str_replace(' ', '%', $compositionEffective) . '%';
            $query->where(function ($w) use ($compositionEffective, $compositionLike) {
                $w->search($compositionEffective, 'composition')
                    ->orWhere('name', 'like', $compositionLike);
            });
        }
        if ($qEffective !== '' || $q !== '') {
            $qSearch = $onlyBrands ? $qEffective : $q;
            $qq = '%' . str_replace(' ', '%', $qSearch) . '%';
            if ($onlyBrands && strtolower($searchBy) === 'brand') {
                $query->where(function ($w) use ($qq) {
                    $w->where('brand_name', 'like', $qq)
                        ->orWhere('name', 'like', $qq)
                        ->orWhere('manufacturer_raw', 'like', $qq)
                        ->orWhereHas('manufacturer', function ($mw) use ($qq) {
                            $mw->where('name', 'like', $qq);
                        });
                });
            } elseif ($onlyBrands && strtolower($searchBy) === 'composition') {
                $query->where(function ($w) use ($qSearch, $qq) {
                    $w->search($qSearch, 'composition')
                        ->orWhere('name', 'like', $qq);
                });
            } else {
                if ($q !== '') {
                    $query->search($q, $searchBy);
                }
            }
        }
        if ($form) {
            $query->where(function ($w) use ($form) {
                $w->whereHas('dosageForm', function ($w) use ($form) {
                    $w->where('name', 'like', '%' . $form . '%');
                })->orWhere('type', 'like', '%' . $form . '%');
            });
        }
        if ($route) {
            $query->whereHas('route', function ($w) use ($route) {
                $w->where('name', 'like', '%' . $route . '%');
            });
        }
        if ($manufacturer) {
            $query->whereHas('manufacturer', function ($w) use ($manufacturer) {
                $w->where('name', 'like', '%' . $manufacturer . '%');
            });
        }
        if ($schedule) {
            $query->where('schedule', $schedule);
        }
        $query->orderBy('name')->orderBy('strength');
        $usePagination = $page > 0 || $perPage > 0;
        $pageResolved = $page > 0 ? $page : 1;
        $perPageResolved = $perPage > 0 ? $perPage : $limit;
        $items = $usePagination
            ? $query->skip(($pageResolved - 1) * $perPageResolved)->take($perPageResolved + 1)->get()
            : $query->limit($limit)->get();
        $hasMore = $usePagination ? $items->count() > $perPageResolved : false;
        if ($usePagination && $hasMore) {
            $items = $items->take($perPageResolved);
        }

        return response()->json([
            'page' => $usePagination ? $pageResolved : null,
            'per_page' => $usePagination ? $perPageResolved : null,
            'has_more' => $hasMore,
            'items' => $items->map(function (Medicine $m) {
                $brandLabel = trim((string) ($m->brand_name ?: $m->name));
                $brandLabelClean = $this->stripDosagePrefix($brandLabel);
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'brand_name' => $m->brand_name,
                    'brand_label' => $brandLabel,
                    'brand_label_clean' => $brandLabelClean,
                    'strength' => $m->strength,
                    'composition_text' => $m->composition_text,
                    'dosage_form' => $m->dosageForm ? $m->dosageForm->name : $m->type,
                    'route' => $m->route ? $m->route->name : null,
                    'therapeutic_class' => $m->therapeutic_class,
                    'schedule' => $m->schedule,
                    'rx_required' => $m->rx_required,
                    'manufacturer' => $m->manufacturer ? $m->manufacturer->name : null,
                    'composition' => $m->ingredients->map(function ($ing) {
                        return [
                            'name' => $ing->name,
                            'strength_value' => $ing->pivot->strength_value,
                            'strength_unit' => $ing->pivot->strength_unit,
                        ];
                    }),
                    'packages' => $m->packages->map(function ($p) {
                        return [
                            'pack_size_value' => $p->pack_size_value,
                            'pack_size_unit' => $p->pack_size_unit,
                            'pack_type' => $p->pack_type,
                            'mrp' => $p->mrp,
                            'hsn_code' => $p->hsn_code,
                            'barcode' => $p->barcode,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show(Medicine $medicine)
    {
        $medicine->load(['manufacturer','dosageForm','route','ingredients','packages']);
        $brandLabel = trim((string) ($medicine->brand_name ?: $medicine->name));
        $brandLabelClean = $this->stripDosagePrefix($brandLabel);
        return response()->json([
            'id' => $medicine->id,
            'name' => $medicine->name,
            'brand_name' => $medicine->brand_name,
            'brand_label' => $brandLabel,
            'brand_label_clean' => $brandLabelClean,
            'strength' => $medicine->strength,
            'composition_text' => $medicine->composition_text,
            'dosage_form' => $medicine->dosageForm ? $medicine->dosageForm->name : $medicine->type,
            'route' => $medicine->route ? $medicine->route->name : null,
            'therapeutic_class' => $medicine->therapeutic_class,
            'schedule' => $medicine->schedule,
            'rx_required' => $medicine->rx_required,
            'manufacturer' => $medicine->manufacturer ? $medicine->manufacturer->name : null,
            'composition' => $medicine->ingredients->map(function ($ing) {
                return [
                    'name' => $ing->name,
                    'strength_value' => $ing->pivot->strength_value,
                    'strength_unit' => $ing->pivot->strength_unit,
                ];
            }),
            'packages' => $medicine->packages->map(function ($p) {
                return [
                    'pack_size_value' => $p->pack_size_value,
                    'pack_size_unit' => $p->pack_size_unit,
                    'pack_type' => $p->pack_type,
                    'mrp' => $p->mrp,
                    'hsn_code' => $p->hsn_code,
                    'barcode' => $p->barcode,
                ];
            }),
        ]);
    }

    private function stripDosagePrefix(string $value): string
    {
        $v = trim((string) $value);
        $v = preg_replace('/\s+/', ' ', $v);
        $v = preg_replace('/^(tab|tablet|cap|capsule|syr|syrup|inj|injection|drop|drops|crm|cream|oint|ointment|gel|soln|solution|susp|suspension)\.?\s+/i', '', $v);
        return trim((string) $v);
    }
}
