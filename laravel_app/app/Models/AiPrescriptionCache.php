<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrescriptionCache extends Model
{
    protected $fillable = [
        'signature_hash',
        'signature_payload',
        'model',
        'ai_analysis',
        'prescription_data',
    ];

    protected $casts = [
        'signature_payload' => 'array',
        'prescription_data' => 'array',
    ];
}

