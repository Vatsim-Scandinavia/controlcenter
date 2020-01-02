<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    protected $dates = [
      'started_at',
      'finished_at'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function reports(){
        return $this->hasMany(TrainingReport::class);
    }

    public function ratings(){
        return $this->belongsToMany(Rating::class);
    }

    public function mentors(){
        return $this->belongsToMany(User::class);
    }
}
