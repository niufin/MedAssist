<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'dispense_order_id',
        'invoice_no',
        'patient_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_total',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function dispenseOrder()
    {
        return $this->belongsTo(DispenseOrder::class, 'dispense_order_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function items()
    {
        return $this->hasMany(PharmacyInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PharmacyPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PharmacyReturn::class);
    }
}

