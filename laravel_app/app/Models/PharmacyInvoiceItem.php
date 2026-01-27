<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_invoice_id',
        'medicine_id',
        'medicine_name',
        'quantity',
        'unit_price',
        'line_total',
        'stock_batch_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(PharmacyInvoice::class, 'pharmacy_invoice_id');
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

