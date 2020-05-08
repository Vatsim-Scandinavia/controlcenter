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
        'id', 'country', 'group', 'last_login'
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
        return $this->hasOne(Handover::class, 'id');
    }

    /**
     * Link user's endorsement
     *
     * @return \App\Solo
     */
    public function soloEndorsement(){
        return $this->hasOne(Solo::class);
    }

    public function trainings(){
        return $this->hasMany(Training::class);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function teaches(){
        return $this->belongsToMany(Training::class);
    }

    public function ratings(){
        return $this->belongsToMany(Rating::class);
    }

    public function settings(){
        return $this->hasMany(UserSetting::class);
    }

    public function bookings(){
        return $this->hasMany(Booking::class);
    }

    public function vatbooks(){
        return $this->hasMany(Vatbook::class);
    }

    // Get properties from Handover, the variable names here break with the convention.
    public function getLastNameAttribute(){
        return $this->handover->last_name;
    }

    public function getFirstNameAttribute(){
        return $this->handover->first_name;
    }

    public function getNameAttribute(){
        return $this->handover->full_name;
    }

    public function getRatingAttribute(){
        return $this->handover->rating;
    }

    public function getRatingShortAttribute(){
        return $this->handover->rating_short;
    }

    public function getRatingLongAttribute(){
        return $this->handover->rating_long;
    }

    // User group checks
    public function isMentor(){
        return $this->group <= 3 && isset($this->group);
    }

    public function isModerator(){
        return $this->group <= 2 && isset($this->group);
    }

    public function isAdmin(){
        return $this->group <= 1 && isset($this->group);
    }
}
