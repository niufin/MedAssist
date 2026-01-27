<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ai_sources' => 'array',
        'prescription_data' => 'array',
    ];

    public function labReports()
    {
        return $this->hasMany(LabReport::class);
    }

    public function prescriptionFulfillments()
    {
        return $this->hasMany(PrescriptionFulfillment::class);
    }

    public function assignedPharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
