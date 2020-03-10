<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
  public function position(){
    return $this->hasOne(Position::class, 'id', 'position_id');
  }

  public function user(){
    return $this->belongsTo(User::class);
  }
}
