<?php

namespace App;

use App\Exceptions\MissingHandoverObjectException;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{

    use Notifiable;

    protected $connection = 'mysql';
    protected $table = 'users';

    public $timestamps = false;
    protected $dates = [
        'last_login',
    ];

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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @throws MissingHandoverObjectException
     */
    public function handover()
    {
        $handover = $this->hasOne(Handover::class, 'id');

        if ($handover->first() == null) {
            throw new MissingHandoverObjectException($this->id);
        }

        return $handover;
    }

    public function soloEndorsement()
    {
        return $this->hasOne(SoloEndorsement::class);
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
        return $this->belongsToMany(Training::class, 'training_mentor')->withPivot('expire_at');
    }

    /**
     * Check is this user is teaching the queried user
     *
     * @param \App\User $user to check for
     * @return bool
     */
    public function isTeaching(User $user){
        return $this->teaches->where('user_id', $user->id)->count() > 0;
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function vatbooks()
    {
        return $this->hasMany(Vatbook::class);
    }

    public function training_role_countries()
    {
        return $this->belongsToMany(Country::class, 'training_role_country')->withTimestamps();
    }

    public function vote(){
        return $this->hasMany(Vote::class);
    }

    // Get properties from Handover, the variable names here break with the convention.
    public function getLastNameAttribute()
    {
        return $this->handover->last_name;
    }

    public function getFirstNameAttribute()
    {
        return $this->handover->first_name;
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getEmailAttribute()
    {
        return $this->handover->email;
    }

    public function getRatingAttribute()
    {
        return $this->handover->rating;
    }

    public function getRatingShortAttribute()
    {
        return $this->handover->rating_short;
    }

    public function getRatingLongAttribute()
    {
        return $this->handover->rating_long;
    }

    public function getDivisionAttribute(){
        return $this->handover->division;
    }

    public function getSubdivisionAttribute(){
        return $this->handover->subdivision;
    }

    public function getCountryAttribute(){
        return $this->handover->country;
    }

    public function getVisitingControllerAttribute(){
        return $this->handover->visiting_controller;
    }

    public function getActiveAttribute(){
        $val = $this->handover->atc_active;

        if ($val == null)
            return false;

        return $val;
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

    /**
     * @return mixed
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function mentoringTrainings()
    {
        $trainings = $this->viewableModels(Training::class, [['status', '>=', 2]])->sortBy('id');

        foreach ($trainings as $key => $training) {
            if (!$training->mentors->contains($this))
                $trainings->pull($key);
        }

        return $trainings;
    }

    /**
     * Get a inline string of ratings associated countries for mentoring.
     *
     * @return string
     */
    public function getInlineMentoringCountries(){

        $output = "";

        if( is_iterable($countries = $this->training_role_countries->toArray()) ){
            for( $i = 0; $i < sizeof($countries); $i++ ){
                if( $i == (sizeof($countries) - 1) ){
                    $output .= $countries[$i]["name"];
                } else {
                    $output .= $countries[$i]["name"] . " & ";
                }
            }
        } else {
            $output .= $countries["name"];
        }

        if(empty($output)){
            $output = "-";
        }

        return $output;
     }

    /**
     * Return whether or not the user has active trainings.
     * A country can be provided to check if the user has an active training in the specified country.
     *
     * @param Country|null $country
     * @return bool
     */
    public function hasActiveTrainings(Country $country = null)
    {
        if ($country == null)
            return count($this->trainings()->whereIn('status', [0, 1, 2, 3])->get()) > 0;

        return count($this->trainings()->where('country_id', $country->id)->whereIn('status', [0, 1, 2, 3])->get()) > 0;
    }

    /**
     * Return the active training for the user
     *
     * @param int $minStatus
     * @param Country|null $country
     * @return Training|null
     */
    public function getActiveTraining(int $minStatus = 0, Country $country = null)
    {
        if ($country == null)
            return $this->trainings()->where([['status', '>=', $minStatus]])->get()->first();

        return $this->trainings()->where([['status', '>=', $minStatus], ['country_id', '=', $country->id]])->get()->first();
    }

    /**
     * Return if user is a mentor
     *
     * @param Country|null $country
     * @return bool
     */
    public function isMentor(Country $country = null)
    {

        if ($country == null) {
            return $this->group <= 3 && isset($this->group);
        }

        return $this->group <= 3 &&
            isset($this->group) &&
            $country->training_roles->contains($this);

    }

    /**
     * Return if user is a moderator
     *
     * @param Country|null $country
     * @return bool
     */
    public function isModerator(Country $country = null)
    {
        if ($country == null)
            return $this->group <= 2 && isset($this->group);

        if ($this->isAdmin())
            return $this->group <= 2 && isset($this->group);

        return $this->group <= 2 &&
            $country->training_roles->contains($this) &&
            isset($this->group);
    }

    /**
     * Return if user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->group <= 1 && isset($this->group);
    }
}
