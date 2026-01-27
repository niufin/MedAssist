<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_invoice_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(PharmacyInvoice::class, 'pharmacy_invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

