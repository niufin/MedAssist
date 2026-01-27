<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_name',
        'strength',
        'type',
        'therapeutic_class',
        'schedule',
        'rx_required',
        'generic_display',
        'manufacturer_id',
        'manufacturer_raw',
        'dosage_form_id',
        'route_id',
        'source_product_id',
        'source_name',
        'is_discontinued',
        'num_active_ingredients',
        'primary_ingredient',
        'primary_strength',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rx_required' => 'boolean',
        'is_discontinued' => 'boolean',
        'num_active_ingredients' => 'integer',
        'generic_display' => 'array',
    ];

    public function getCompositionTextAttribute(): ?string
    {
        $gd = $this->generic_display;
        $compText = is_array($gd) ? ($gd['text'] ?? null) : $gd;
        $compText = is_string($compText) ? trim($compText) : null;
        if (!empty($compText)) {
            return $compText;
        }
        if (!empty($this->primary_ingredient)) {
            return trim($this->primary_ingredient . (!empty($this->primary_strength) ? (' ' . $this->primary_strength) : ''));
        }
        if ($this->relationLoaded('ingredients')) {
            $parts = [];
            foreach ($this->ingredients as $ing) {
                $n = trim((string) ($ing->name ?? ''));
                if ($n === '') {
                    continue;
                }
                $sv = $ing->pivot?->strength_value;
                $su = $ing->pivot?->strength_unit;
                $s = trim((string) ($sv !== null ? $sv : ''));
                $u = trim((string) ($su ?? ''));
                $parts[] = trim($n . (($s !== '' || $u !== '') ? (' ' . trim($s . ' ' . $u)) : ''));
                if (count($parts) >= 6) {
                    break;
                }
            }
            $txt = trim(implode(' + ', $parts));
            return $txt !== '' ? $txt : null;
        }
        return null;
    }

    public function scopeSearch(Builder $query, string $term, string $searchBy = 'all'): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $searchBy = strtolower(trim($searchBy));
        if ($searchBy === '') {
            $searchBy = 'all';
        }
        $like = '%' . str_replace(' ', '%', $term) . '%';
        $driver = DB::getDriverName();

        $applyIngredientExists = function (Builder $q) use ($term, $like, $driver) {
            $q->orWhereExists(function ($sq) use ($term, $like, $driver) {
                $sq->select(DB::raw(1))
                    ->from('medicine_ingredients')
                    ->join('ingredients', 'ingredients.id', '=', 'medicine_ingredients.ingredient_id')
                    ->whereColumn('medicine_ingredients.medicine_id', 'medicines.id')
                    ->where(function ($qq) use ($term, $like, $driver) {
                        $qq->where('ingredients.name', 'like', $like)
                            ->orWhere('ingredients.synonyms', 'like', $like);
                        if ($driver === 'mysql') {
                            $qq->orWhereRaw('SOUNDEX(ingredients.name) = SOUNDEX(?)', [$term]);
                        }
                    });
            });
        };

        return $query->where(function (Builder $q) use ($term, $like, $searchBy, $driver, $applyIngredientExists) {
            if ($searchBy === 'name') {
                $q->where('name', 'like', $like);
                return;
            }
            if ($searchBy === 'brand') {
                $q->where('brand_name', 'like', $like);
                return;
            }
            if ($searchBy === 'strength') {
                $q->where('strength', 'like', $like)
                    ->orWhere('primary_strength', 'like', $like);
                return;
            }
            if ($searchBy === 'manufacturer') {
                $q->whereHas('manufacturer', function ($w) use ($like) {
                    $w->where('name', 'like', $like);
                });
                return;
            }
            if ($searchBy === 'class') {
                $q->where('therapeutic_class', 'like', $like);
                return;
            }
            if ($searchBy === 'composition') {
                $q->where('primary_ingredient', 'like', $like)
                    ->orWhere('generic_display', 'like', $like);
                $applyIngredientExists($q);
                if ($driver === 'mysql') {
                    $q->orWhereRaw('SOUNDEX(primary_ingredient) = SOUNDEX(?)', [$term]);
                }
                return;
            }

            $q->where('name', 'like', $like)
                ->orWhere('brand_name', 'like', $like)
                ->orWhere('strength', 'like', $like)
                ->orWhere('type', 'like', $like)
                ->orWhere('therapeutic_class', 'like', $like)
                ->orWhere('primary_ingredient', 'like', $like)
                ->orWhere('generic_display', 'like', $like)
                ->orWhereHas('manufacturer', function ($w) use ($like) {
                    $w->where('name', 'like', $like);
                })
                ->orWhereHas('ingredients', function ($w) use ($like) {
                    $w->where('ingredients.name', 'like', $like)
                        ->orWhere('ingredients.synonyms', 'like', $like);
                });
            if ($driver === 'mysql') {
                $q->orWhereRaw('SOUNDEX(primary_ingredient) = SOUNDEX(?)', [$term]);
            }
        });
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'medicine_ingredients')
            ->withPivot(['strength_value', 'strength_unit'])
            ->withTimestamps();
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function dosageForm()
    {
        return $this->belongsTo(DosageForm::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class)
            ->orderBy('mrp')
            ->orderBy('price_inr');
    }
}
