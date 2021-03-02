<?php

namespace App\Models;

use App\Exceptions\MissingHandoverObjectException;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{

    use HasFactory, Notifiable;

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

    public function teaches()
    {
        return $this->belongsToMany(Training::class, 'training_mentor')->withPivot('expire_at');
    }

    /**
     * Check is this user is teaching the queried user
     *
     * @param  \App\Models\User $user to check for
     * @return bool
     */
    public function isTeaching(User $user){
        return $this->teaches->where('user_id', $user->id)->count() > 0;
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'permissions')->withPivot('country_id')->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function vatbooks()
    {
        return $this->hasMany(Vatbook::class);
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
     * Get a inline string of ratings associated areas for mentoring.
     *
     * @return string
     */
    public function getInlineMentoringCountries(){

        $output = "";

        if($this->groups->count() > 1){
            foreach($this->groups as $group){
                $output .= Country::find($group->pivot->country_id)->name . " & ";
            }
        } else {
            $output .= Country::find($this->groups->first()->pivot->country_id)->name;
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
            return $this->groups()->where('id',  3)->exists();
        }

        return $this->groups()->where('id', 3)->wherePivot('country_id', $country->id)->exists();

    }

    /**
     * Return if user is a mentor or above
     *
     * @param Country|null $country
     * @return bool
     */
    public function isMentorOrAbove(Country $country = null)
    {

        if ($country == null) {
            return $this->groups()->where('id', '<=',  3)->exists();
        }

        return $this->groups()->where('id', '<=', 3)->wherePivot('country_id', $country->id)->exists();

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
            return $this->groups()->where('id', 2)->exists();

        if ($this->isAdmin())
            return $this->groups()->where('id', 2)->exists();

        return $this->groups()->where('id', 2)->wherePivot('country_id', $country->id)->exists();
    }

    /**
     * Return if user is a moderator or above
     *
     * @param Country|null $country
     * @return bool
     */
    public function isModeratorOrAbove(Country $country = null)
    {
        if ($country == null)
            return $this->groups()->where('id', '<=', 2)->exists();

        if ($this->isAdmin())
            return $this->groups()->where('id', '<=', 2)->exists();

        return $this->groups()->where('id', '<=', 2)->wherePivot('country_id', $country->id)->exists();
    }

    /**
     * Return if user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains('id', 1);
    }
}
