<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserEndorsement extends Model
{

    protected $dates = ['expires_at'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function training(){
        return $this->belongsTo(Country::class);
    }

}