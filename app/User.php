<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     
    protected $fillable = [
        'id', 'visiting_controller', 'last_login', 'usergroup'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token'
    ];

    /**
     * Link to handover data
     * 
     * @return \App\Handover
     */
    public function handover(){
        return $this->belongsTo(Handover::class, 'id');
    }

    /**
     * Link user's endorsement
     * 
     * @return \App\SoloEndorsement
     */
    public function solo_endorsement(){
        return $this->hasOne(SoloEndorsement::class);
    }

    public function trainings(){
        return $this->hasMany(Training::class);
    }

    public function usergroup(){
        return $this->belongsTo(UserGroup::class, 'usergroup');
    }
}
