<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_invoice_id',
        'refund_total',
        'status',
        'user_id',
    ];

    protected $casts = [
        'refund_total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(PharmacyInvoice::class, 'pharmacy_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(PharmacyReturnItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

