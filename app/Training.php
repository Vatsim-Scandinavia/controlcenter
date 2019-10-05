<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function training_reports(){
        return $this->hasMany(TrainingReport::class);
    }

    public function training_ratings(){
        return $this->hasMany(TrainingRequestRating::class);
    }

    public function mentors(){
        return $this->hasMany(AssignedMentor::class);
    }
}
