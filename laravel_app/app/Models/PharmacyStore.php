<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_admin_id',
        'name',
        'address',
        'contact_number',
        'low_stock_threshold',
        'near_expiry_days',
    ];

    protected $casts = [
        'low_stock_threshold' => 'integer',
        'near_expiry_days' => 'integer',
    ];

    public function hospitalAdmin()
    {
        return $this->belongsTo(User::class, 'hospital_admin_id');
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}

