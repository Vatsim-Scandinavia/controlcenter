<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    public $timestamps = false;

    public function trainings(){
        return $this->belongsToMany(Training::class);
    }

    public function countries(){
        return $this->belongsToMany(Country::class)->withPivot('required_vatsim_rating');
    }

    public function users(){
        return $this->belongsToMany(Rating::class);
    }
}
