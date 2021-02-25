<?php

namespace App\Models;

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

    public function permissions(){
        return $this->hasMany(Permission::class);
    }

    public function mentors()
    {
        return $this->belongsToMany(User::class, 'training_role_country')->withTimestamps();
    }

    public function training_roles()
    {
        return $this->belongsToMany(User::class, 'training_role_country')->withTimestamps();
    }

    public function positions(){
        return $this->hasMany(Position::class, 'country');
    }
}


