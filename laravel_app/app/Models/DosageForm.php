<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DosageForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }
}

