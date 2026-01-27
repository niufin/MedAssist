<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'pack_size_value',
        'pack_size_unit',
        'pack_type',
        'mrp',
        'price_inr',
        'hsn_code',
        'barcode',
        'packaging_raw',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
