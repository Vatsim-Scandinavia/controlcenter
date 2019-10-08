<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingRequestRating extends Model
{
    public function training(){
        return $this->belongsTo(Training::class);
    }

    public function rating(){
        return $this->belongsTo(Rating::class);
    }
}
