<?php

namespace App;

use App\Exceptions\MissingHandoverObjectException;
use App\Exceptions\PolicyMissingException;
use App\Exceptions\PolicyMethodMissingException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{

    use Notifiable;

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
    public function handover()
    {
        return $this->hasOne(Handover::class, 'id');
    }

    /**
     * Link user's endorsement
     *
     * @return \App\Solo
     */
    public function soloEndorsement()
    {
        return $this->hasOne(Solo::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function teaches()
    {
        return $this->belongsToMany(Training::class);
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function vatbooks()
    {
        return $this->hasMany(Vatbook::class);
    }

    public function mentor_countries()
    {
        return $this->belongsToMany(Country::class, 'mentor_country');
    }

    // Get properties from Handover, the variable names here break with the convention.
    public function getLastNameAttribute()
    {
        return $this->getHandoverAttr('last_name');
    }

    public function getFirstNameAttribute()
    {
        return $this->getHandoverAttr('first_name');
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getRatingAttribute()
    {
        return $this->getHandoverAttr('rating');
    }

    public function getRatingShortAttribute()
    {
        return $this->getHandoverAttr('rating_short');
    }

    public function getRatingLongAttribute()
    {
        return $this->getHandoverAttr('rating_long');
    }

    public function getDivisionAttribute(){
        return $this->getHandoverAttr('division');
    }

    public function getSubdivisionAttribute(){
        return $this->getHandoverAttr('subdivision');
    }

    public function getVisitingControllerAttribute(){
        return $this->getHandoverAttr('visiting_controller');
    }

    public function getActiveAttribute(){
        return $this->getHandoverAttr('atc_active');
    }

    /**
     * Get an attribute from the user's Handover object
     *
     * @param string $key
     * @return mixed
     * @throws MissingHandoverObjectException
     */
    public function getHandoverAttr(string $key)
    {
        $handover = $this->handover;

        if ($handover == null) {
            throw new MissingHandoverObjectException($this->id);
        }

        return $handover->$key;
    }

    /**
     * Get the models allowed for the user to be viewed.
     *
     * @param $class
     * @param array $options
     * @return mixed
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function viewableModels($class, array $options = [])
    {

        if (policy($class) == null) {
            throw new PolicyMissingException();
        }

        if (!method_exists(policy($class), 'view')) {
            throw new PolicyMethodMissingException('The view method does not exist on the policy.');
        }

        $models = $class::where($options)->get();

        foreach ($models as $key => $model) {
            if ($this->cannot('view', $model)) {
                $models->pull($key);
            }
        }

        return $models;

    }

    // User group checks
    public function isMentor(Country $country = null)
    {

        if ($country == null) {
            return $this->group <= 3 && isset($this->group);
        }

        return $this->group <= 3 &&
            isset($this->group) &&
            $country->mentors->contains($this);

    }

    public function isModerator()
    {
        return $this->group <= 2 && isset($this->group);
    }

    public function isAdmin()
    {
        return $this->group <= 1 && isset($this->group);
    }
}
