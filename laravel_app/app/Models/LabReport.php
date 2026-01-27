<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'file_path',
        'notes',
        'uploaded_by',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
