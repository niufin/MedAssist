<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_return_id',
        'pharmacy_invoice_item_id',
        'quantity',
        'refund_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    public function return()
    {
        return $this->belongsTo(PharmacyReturn::class, 'pharmacy_return_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(PharmacyInvoiceItem::class, 'pharmacy_invoice_item_id');
    }
}

