<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssignedMentor extends Model
{
    public function mentor(){
        return $this->belongsTo(User::class);
    }

    public function training(){
        return $this->belongsTo(Training::class);
    }
}
