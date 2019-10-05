<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Link to handover data
     * 
     * @return \App\Handover
     */
    public function handover(){
        return $this->hasOne(Handover::class, 'id');
    }

    /**
     * Link user's endorsement
     * 
     * @return \App\Endorsement
     */
    public function endorsement(){
        return $this->hasOne(Endorsement::class);
    }
}
