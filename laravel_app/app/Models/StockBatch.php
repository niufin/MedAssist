<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'medicine_id',
        'batch_no',
        'expiry_date',
        'mrp',
        'purchase_price',
        'sale_price',
        'quantity_on_hand',
        'rack_location',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'mrp' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'quantity_on_hand' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}

