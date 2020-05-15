<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

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

    public function mentor_countries()
    {
        return $this->belongsToMany(Country::class, 'mentor_country');
    }

    // Get properties from Handover, the variable names here break with the convention.
    public function getLastNameAttribute(){
        return $this->handover->last_name;
    }

    public function getFirstNameAttribute(){
        return $this->handover->first_name;
    }

    public function getNameAttribute(){
        return $this->first_name . " " . $this->last_name;
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

    /**
     * Get the models allowed for the user to be viewed.
     *
     * @param $class
     * @param array $options
     * @return mixed
     */
    public function viewableModels($class, array $options = [])
    {

        $models = $class::where($options)->get();

        foreach ($models as $key => $model) {
            if ($this->cannot('view', $model)) {
                $models->pull($key);
            }
        }

        return $models;

    }

    // User group checks
    public function isMentor(Country $country = null) {

        if ($country == null) {
            return $this->group <= 3 && isset($this->group);
        }

        return  $this->group <= 3 &&
                isset($this->group) &&
                $country->mentors->contains($this);

    }

    public function isModerator(){
        return $this->group <= 2 && isset($this->group);
    }

    public function isAdmin(){
        return $this->group <= 1 && isset($this->group);
    }
}
