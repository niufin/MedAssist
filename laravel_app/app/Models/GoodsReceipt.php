<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'purchase_order_id',
        'grn_no',
        'status',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}

