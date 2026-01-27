<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'medicine_id',
        'stock_batch_id',
        'quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

