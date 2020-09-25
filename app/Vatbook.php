<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vatbook extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['eu_id', 'local_id', 'callsign', 'position_id', 'name', 'time_start', 'time_end', 'cid', 'user_id', 'training', 'event'];

  public function position(){
    return $this->hasOne(Position::class, 'id', 'position_id');
  }

  public function user(){
    return $this->belongsTo(User::class);
  }
}