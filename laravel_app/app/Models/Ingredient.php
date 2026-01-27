<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'synonyms',
        'atc_code',
    ];

    protected $casts = [
        'synonyms' => 'array',
    ];

    public function medicines()
    {
        return $this->belongsToMany(Medicine::class, 'medicine_ingredients')
            ->withPivot(['strength_value', 'strength_unit'])
            ->withTimestamps();
    }
}

