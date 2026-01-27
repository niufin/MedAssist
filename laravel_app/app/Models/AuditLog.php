<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_store_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(PharmacyStore::class, 'pharmacy_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

