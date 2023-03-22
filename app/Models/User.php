<?php

namespace App\Models;

use App\Exceptions\MissingHandoverObjectException;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use App\Helpers\VatsimRating;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $dates = [
        'last_login',
        'last_activity',
        'last_inactivity_warning',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'last_login',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Link to handover data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     *
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

    /**
     * Relationship of all permissions to this user
     *
     * @return Illuminate\Database\Eloquent\Collection|Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'permissions')->withPivot('area_id')->withTimestamps();
    }

    /**
     * Find all users with queried group
     *
     * @param  int  $groupId the id of the group to check for
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function allWithGroup($groupId, $IneqSymbol = '=')
    {
        return User::whereHas('groups', function ($query) use ($groupId, $IneqSymbol) {
            $query->where('id', $IneqSymbol, $groupId);
        })
        ->get();
    }

    public function endorsements()
    {
        return $this->hasMany(Endorsement::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function trainingActivities()
    {
        return $this->hasMany(TrainingActivity::class);
    }

    public function teaches()
    {
        return $this->belongsToMany(Training::class, 'training_mentor')->withPivot('expire_at');
    }

    /**
     * Check is this user is teaching the queried user
     *
     * @param  \App\Models\User  $user to check for
     * @return bool
     */
    public function isTeaching(User $user)
    {
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

    public function vote()
    {
        return $this->hasMany(Vote::class);
    }

    public function atcActivity()
    {
        return $this->hasOne(AtcActivity::class);
    }

    // TODO: decide if we should nuke me from orbit
    public function atchours()
    {
        $atcHoursDB = AtcActivity::where('user_id', $this->id)->get()->first();

        return ($atcHoursDB == null) ? null : $atcHoursDB->hours;
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
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getEmailAttribute()
    {
        if ($this->setting_workmail_address) {
            return $this->setting_workmail_address;
        }

        return $this->handover->email;
    }

    /**
     * The VATSIM rating value of a given user.
     *
     * @todo Return @{VatsimRating} instead of integer?
     */
    public function getRatingAttribute(): int
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

    public function getDivisionAttribute()
    {
        return $this->handover->division;
    }

    public function getSubdivisionAttribute()
    {
        return $this->handover->subdivision;
    }

    public function getCountryAttribute()
    {
        return $this->handover->country;
    }

    public function getActiveAttribute()
    {
        $val = $this->handover->atc_active;

        if ($val == null) {
            return false;
        }

        return $val;
    }

    /**
     * Get the models allowed for the user to be viewed.
     *
     * @return mixed
     *
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function viewableModels($class, array $options = [], array $with = [])
    {
        if (policy($class) == null) {
            throw new PolicyMissingException();
        }

        if (! method_exists(policy($class), 'view')) {
            throw new PolicyMethodMissingException('The view method does not exist on the policy.');
        }

        $models = $class::where($options)->with($with)->get();

        foreach ($models as $key => $model) {
            if ($this->cannot('view', $model)) {
                $models->pull($key);
            }
        }

        return $models;
    }

    /**
     * @return mixed
     *
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function mentoringTrainings()
    {
        $trainings = Training::where('status', '>=', 1)->whereHas('mentors', function ($query) {
            $query->where('user_id', $this->id);
        })->orderBy('id')->get();

        return $trainings;
    }

    /**
     * Get a inline string of ratings associated areas for mentoring.
     *
     * @return string
     */
    public function getInlineMentoringAreas()
    {
        $areas = Area::whereHas('permissions', function ($query) {
            $query->where('user_id', $this->id);
        })->get();

        return $areas ? $areas->pluck('name')->implode(' & ') : ' - ';
    }

    /**
     * Return whether or not the user has active trainings.
     * A area can be provided to check if the user has an active training in the specified area.
     *
     * @return bool
     */
    public function hasActiveTrainings(bool $includeWaiting, Area $area = null)
    {
        if ($includeWaiting) {
            if ($area == null) {
                return count($this->trainings()->whereIn('status', [0, 1, 2, 3])->get()) > 0;
            }

            return count($this->trainings()->where('area_id', $area->id)->whereIn('status', [0, 1, 2, 3])->get()) > 0;
        } else {
            if ($area == null) {
                return count($this->trainings()->whereIn('status', [1, 2, 3])->get()) > 0;
            }

            return count($this->trainings()->where('area_id', $area->id)->whereIn('status', [1, 2, 3])->get()) > 0;
        }
    }

    /**
     * Return the active training for the user
     *
     * @return Training|null
     */
    public function getActiveTraining(int $minStatus = 0, Area $area = null)
    {
        if ($area == null) {
            return $this->trainings()->where([['status', '>=', $minStatus]])->get()->first();
        }

        return $this->trainings()->where([['status', '>=', $minStatus], ['area_id', '=', $area->id]])->get()->first();
    }

    /**
     * Return if the user has specified MASC endorsement
     *
     * @return bool
     */
    public function hasEndorsementRating(Rating $rating)
    {
        foreach ($this->endorsements->where('type', 'MASC')->where('revoked', false)->where('expired', false) as $e) {
            foreach ($e->ratings as $r) {
                if ($r->id == $rating->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return if the user has an active endorsement of type
     *
     * @param  bool  $onlyInfinteEndorsements
     * @return bool
     */
    public function hasActiveEndorsement(string $type, $onlyInfinteEndorsements = false)
    {
        if ($onlyInfinteEndorsements) {
            return Endorsement::where('user_id', $this->id)->where('type', $type)->where('revoked', false)->where('expired', false)->where('valid_to', null)->exists();
        } else {
            return Endorsement::where('user_id', $this->id)->where('type', $type)->where('revoked', false)->where('expired', false)->exists();
        }
    }

    /**
     * Return if the user has recently finished a training
     *
     * @param  string  $type
     * @return bool
     */
    public function hasRecentlyCompletedTraining()
    {
        $training = $this->trainings->where('status', -1)->where('closed_at', '>', Carbon::now()->subDays(7))->first();

        if ($training == null) {
            return false;
        }
        if ($training->isMaeTraining()) {
            return false;
        }

        return true;
    }

    /**
     * Return if user is visiting
     *
     * @return bool
     */
    public function isVisiting(Area $area = null)
    {
        if ($area == null) {
            return $this->endorsements->where('type', 'VISITING')->where('revoked', false)->where('expired', false)->count();
        }

        // Check if the user has an active examiner endorsement for the area
        if ($this->endorsements->where('type', 'VISITING')->where('revoked', false)->where('expired', false)->first()) {
            return $this->endorsements->where('type', 'VISITING')->where('revoked', false)->where('expired', false)->first()->areas()->wherePivot('area_id', $area->id)->count();
        }

        return false;
    }

    /**
     * Return if user is an examiner
     *
     * @return bool
     */
    public function isExaminer(Area $area = null)
    {
        if ($area == null) {
            return $this->endorsements->where('type', 'EXAMINER')->where('revoked', false)->where('expired', false)->count();
        }

        // Check if the user has an active examiner endorsement for the area
        if ($this->endorsements->where('type', 'EXAMINER')->where('revoked', false)->where('expired', false)->first()) {
            return $this->endorsements->where('type', 'EXAMINER')->where('revoked', false)->where('expired', false)->first()->areas()->wherePivot('area_id', $area->id)->count();
        }

        return false;
    }

    /**
     * Return if user is a mentor
     *
     * @return bool
     */
    public function isMentor(Area $area = null)
    {
        if ($area == null) {
            return $this->groups()->where('id', 3)->exists();
        }

        return $this->groups()->where('id', 3)->wherePivot('area_id', $area->id)->exists();
    }

    /**
     * Return if user is a mentor or above
     *
     * @return bool
     */
    public function isMentorOrAbove(Area $area = null)
    {
        if ($area == null) {
            return $this->groups()->where('id', '<=', 3)->exists();
        }

        return $this->groups()->where('id', '<=', 3)->wherePivot('area_id', $area->id)->exists();
    }

    /**
     * Return if user is a moderator
     *
     * @return bool
     */
    public function isModerator(Area $area = null)
    {
        if ($area == null) {
            return $this->groups()->where('id', 2)->exists();
        }

        return $this->groups()->where('id', 2)->wherePivot('area_id', $area->id)->exists();
    }

    /**
     * Return if user is a moderator or above
     *
     * @return bool
     */
    public function isModeratorOrAbove(Area $area = null)
    {
        if ($area == null) {
            return $this->groups()->where('id', '<=', 2)->exists();
        }

        if ($this->isAdmin()) {
            return $this->groups()->where('id', '<=', 2)->exists();
        }

        return $this->groups()->where('id', '<=', 2)->wherePivot('area_id', $area->id)->exists();
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
