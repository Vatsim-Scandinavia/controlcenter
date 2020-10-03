<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingInterest extends Model
{
    protected $guarded = [];

    protected $dates = [
        'deadline',
        'confirmed_at'
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

}
