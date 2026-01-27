<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispenseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispense_order_id',
        'medicine_id',
        'medicine_name',
        'dosage',
        'frequency',
        'duration',
        'instruction',
        'quantity',
        'dispensed_quantity',
        'stock_batch_id',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'dispensed_quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(DispenseOrder::class, 'dispense_order_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}

