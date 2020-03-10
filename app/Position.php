<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
  public $timestamps = false;

  public function bookings(){
    return $this->belongsToMany(Booking::class, 'id', 'position_id');
  }
}
