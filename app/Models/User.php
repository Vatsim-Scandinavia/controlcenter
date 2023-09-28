<?php

namespace App\Models;

use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use App\Helpers\VatsimRating;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $casts = [
        'last_login' => 'datetime',
        'last_activity' => 'datetime',
        'last_inactivity_warning' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'email', 'first_name', 'last_name', 'rating', 'rating_short', 'rating_long', 'region', 'division', 'subdivision', 'last_login', 'access_token', 'refresh_token', 'token_expires',
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

    public function trainingReports()
    {
        return $this->hasMany(TrainingReport::class, 'written_by_id');
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

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @todo: Convert to to new v9.x+ mutators https://laravel.com/docs/9.x/eloquent-mutators
     */
    public function getEmailAttribute($value)
    {
        if ($this->setting_workmail_address) {
            return $this->setting_workmail_address;
        }

        return $value;
    }

    public function getActiveAttribute()
    {
        $val = $this->atc_active;

        if ($val == null) {
            return false;
        }

        return $val;
    }

    /**
     * Fetch members that are active as ATC.
     *
     * @return EloquentCollection<User>
     */
    public static function getActiveAtcMembers(array $userIds = [])
    {
        // Return S1+ users who are VATSCA members
        if (! empty($userIds)) {
            return User::whereIn('id', $userIds)
                ->where('atc_active', true)
                ->get();
        } else {
            return User::where('atc_active', true)->get();
        }
    }

    /**
     * Fetch members with a rating that are in our subdivision
     *
     * @return EloquentCollection<User>
     */
    public static function getRatedMembers(array $userIds = [])
    {
        // Return S1+ users who are VATSCA members
        if (! empty($userIds)) {
            return User::whereIn('id', $userIds)
                ->where('rating', '>=', VatsimRating::S1)
                ->where('subdivision', Config::get('app.owner_short'))
                ->get();
        } else {
            return User::where([
                ['rating', '>=', VatsimRating::S1],
                ['subdivision', '=', Config::get('app.owner_short')],
            ])->get();
        }
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
        })->with('area', 'ratings', 'reports', 'user')->orderBy('id')->get();

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
            return $this->groups->where('id', 3)->isNotEmpty();
        }

        // Check if user is mentor in the specified area
        foreach ($this->groups->where('id', 3) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is a mentor or above
     *
     * @return bool
     */
    public function isMentorOrAbove(Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', 3)->isNotEmpty();
        }

        // Check if user is mentor or above in the specified area
        foreach ($this->groups->where('id', '<=', 3) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is a moderator
     *
     * @return bool
     */
    public function isModerator(Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', 2)->isNotEmpty();
        }

        // Check if user is moderator in the specified area
        foreach ($this->groups->where('id', 2) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is a moderator or above
     *
     * @return bool
     */
    public function isModeratorOrAbove(Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', 2)->isNotEmpty();
        }

        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is moderator or above in the specified area
        foreach ($this->groups->where('id', '<=', 2) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
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
