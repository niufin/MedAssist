<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionFulfillment extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'medicine_name',
        'status',
        'notes',
        'pharmacist_id',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }
}
