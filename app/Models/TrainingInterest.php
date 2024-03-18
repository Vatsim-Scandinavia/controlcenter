<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingInterest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'deadline' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
