<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispenseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'consultation_id',
        'patient_id',
        'doctor_id',
        'pharmacist_id',
        'status',
        'dispensed_at',
        'notes',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function items()
    {
        return $this->hasMany(DispenseItem::class);
    }
}

