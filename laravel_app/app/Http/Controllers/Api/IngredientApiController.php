<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientApiController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) ($request->query('limit', 25));
        if ($limit < 1) $limit = 25;
        if ($limit > 100) $limit = 100;

        $query = Ingredient::query();
        if ($q !== '') {
            $qq = '%' . str_replace(' ', '%', $q) . '%';
            $query->where(function ($w) use ($qq) {
                $w->where('name', 'like', $qq);
            });
        }
        $query->orderBy('name');
        $items = $query->limit($limit)->get();

        return response()->json([
            'items' => $items->map(function (Ingredient $ing) {
                return [
                    'id' => $ing->id,
                    'name' => $ing->name,
                    'synonyms' => $ing->synonyms,
                    'atc_code' => $ing->atc_code,
                ];
            }),
        ]);
    }
}

