<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{

    public $timestamps = false;

    public function trainings(){
        return $this->hasMany(Training::class);
    }

    public function ratings(){
        return $this->belongsToMany(Rating::class)->withPivot('required_vatsim_rating', 'queue_length');
    }

    public function mentors()
    {
        return $this->belongsToMany(User::class, 'mentor_country');
    }

}


