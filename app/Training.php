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
}
