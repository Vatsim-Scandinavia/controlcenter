<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    public $timestamps = false;

    public function trainings(){
        return $this->belongsToMany(Training::class);
    }

    public function areas(){
        return $this->belongsToMany(Area::class)->withPivot('required_vatsim_rating', 'queue_length');
    }

    public function users(){
        return $this->belongsToMany(User::class);
    }
}
