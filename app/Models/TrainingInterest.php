<?php

namespace App\Models;

use App\Helpers\InterestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingInterest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'expired' => InterestStatus::class,
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
