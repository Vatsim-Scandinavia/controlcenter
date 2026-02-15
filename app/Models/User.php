<?php

namespace App\Models;

use anlutro\LaravelSettings\Facade as Setting;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

const GROUP_ADMINISTRATOR = 1;
const GROUP_MODERATOR = 2;
const GROUP_MENTOR = 3;
const GROUP_BUDDY = 4;

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
        'id', 'email', 'first_name', 'last_name', 'rating', 'rating_short', 'rating_long', 'region', 'division', 'subdivision', 'atc_active', 'last_login', 'access_token', 'refresh_token', 'token_expires',
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
     * @param  int  $groupId  the id of the group to check for
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function allWithGroup($groupId, $IneqSymbol = '=')
    {
        return User::whereHas('groups', function ($query) use ($groupId, $IneqSymbol) {
            $query->where('id', $IneqSymbol, $groupId);
        })->get();
    }

    /**
     * Find all users with queried group in the specified area
     *
     * @param  Area  $area  the area to check for
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function allActiveInArea(Area $area)
    {
        $query = User::join('atc_activities', 'users.id', '=', 'atc_activities.user_id')
            ->where('atc_activities.area_id', $area->id)
            ->where('atc_activities.atc_active', true)
            ->select('users.*', 'atc_activities.last_online', 'atc_activities.hours_in_period', 'atc_activities.hours', 'atc_activities.start_of_grace_period')
            ->with(['endorsements']);

        if (Setting::get('atcActivityBasedOnTotalHours')) {
            $query->where(function ($query) {
                $query->where('atc_activities.start_of_grace_period', '>', now()->subMonths(Setting::get('atcActivityGracePeriod', 12)))
                    ->orWhere('atc_activities.hours', '>=', 0);
            });
        } else {
            $query->where(function ($query) {
                $query->where('atc_activities.start_of_grace_period', '>', now()->subMonths(Setting::get('atcActivityGracePeriod', 12)))
                    ->orWhere('atc_activities.hours', '>=', Setting::get('atcActivityRequirement', 10));
            });
        }

        return $query->get();
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
     * @param  \App\Models\User  $user  to check for
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

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignee_user_id');
    }

    public function atcActivity()
    {
        return $this->hasMany(AtcActivity::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function submittedFeedback()
    {
        return $this->hasMany(Feedback::class, 'submitter_user_id');
    }

    public function receivedFeedback()
    {
        return $this->hasMany(Feedback::class, 'reference_user_id');
    }

    public function getPersonalNotificationEmailAttribute()
    {
        return $this->email;
    }

    public function getWorkNotificationEmailAttribute()
    {
        if ($this->setting_workmail_address) {
            return $this->setting_workmail_address;
        }

        return $this->email;
    }

    /**
     * Check if the user is active as ATC
     *
     * @return bool
     */
    public function isAtcActive(?Area $area = null)
    {
        if (Setting::get('atcActivityBasedOnTotalHours')) {

            $atLeastOneAreaActive = AtcActivity::where('user_id', $this->id)->where('atc_active', true)->exists();

            $hasEnoughHours = $this->atcActivity->sum('hours') >= Setting::get('atcActivityRequirement', 10);
            $isInGracePeriod = $this->atcActivity->where('start_of_grace_period', '>', now()->subMonths(Setting::get('atcActivityGracePeriod', 12)))->count() > 0;

            return $atLeastOneAreaActive && ($hasEnoughHours || $isInGracePeriod);
        } else {
            if ($area) {
                return AtcActivity::where('user_id', $this->id)->where('atc_active', true)->where('area_id', $area->id)->exists();
            }

            return AtcActivity::where('user_id', $this->id)->where('atc_active', true)->exists();
        }

    }

    /**
     * Check if the user is allowed to control online
     *
     * @return bool
     */
    public function isAllowedToControlOnline(?Area $area = null)
    {

        if (
            ! $this->isVisiting($area) &&
            ! $this->isAtcActive($area) &&
            ! $this->hasActiveTrainings(false, $area) &&
            ! $this->hasRecentlyCompletedTraining()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Fetch members that are active as ATC.
     *
     * @return EloquentCollection<User>
     */
    public static function getActiveAtcMembers(array $userIds = [])
    {
        // Return S1+ users who are VATSCA members and active as ATC
        if (! empty($userIds)) {
            return User::whereIn('id', $userIds)
                ->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                })->get();
        } else {
            return User::whereHas('atcActivity', function ($query) {
                $query->where('atc_active', true);
            })->get();
        }
    }

    /**
     * Fetch members that are active as ATC and associated with the division.
     *
     * @return EloquentCollection<User>
     */
    public static function getAssociatedActiveAtcMembers(bool $onlyCheckActiveControllers = true, array $userIds = [])
    {
        // Return S1+ users who are VATSCA members and active as ATC
        if (! empty($userIds)) {
            $query = User::whereIn('id', $userIds)
                ->where(config('app.mode'), config('app.owner_code'));

            if ($onlyCheckActiveControllers) {
                $query->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }

            return $query->get();
        } else {
            $query = User::where(config('app.mode'), config('app.owner_code'));
            if ($onlyCheckActiveControllers) {
                $query->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }

            return $query->get();
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
    public function hasActiveTrainings(bool $includeWaiting, ?Area $area = null)
    {
        $statuses = $includeWaiting ? [0, 1, 2, 3] : [1, 2, 3];

        if ($this->relationLoaded('trainings')) {
            $trainings = $this->trainings;
            if ($area) {
                $trainings = $trainings->where('area_id', $area->id);
            }

            return $trainings->whereIn('status', $statuses)->isNotEmpty();
        }

        if ($includeWaiting) {
            if ($area == null) {
                return $this->trainings()->whereIn('status', [0, 1, 2, 3])->exists();
            }

            return $this->trainings()->where('area_id', $area->id)->whereIn('status', [0, 1, 2, 3])->exists();
        } else {
            if ($area == null) {
                return $this->trainings()->whereIn('status', [1, 2, 3])->exists();
            }

            return $this->trainings()->where('area_id', $area->id)->whereIn('status', [1, 2, 3])->exists();
        }
    }

    /**
     * Return the active training for the user
     *
     * @return Training|null
     */
    public function getActiveTraining(int $minStatus = 0, ?Area $area = null)
    {
        if ($area == null) {
            return $this->trainings()->where([['status', '>=', $minStatus]])->get()->first();
        }

        return $this->trainings()->where([['status', '>=', $minStatus], ['area_id', '=', $area->id]])->get()->first();
    }

    /**
     * Return if the user has specified FACILITY endorsement
     *
     * @return bool
     */
    public function hasEndorsementRating(Rating $rating)
    {
        foreach ($this->endorsements->where('type', 'FACILITY')->where('revoked', false)->where('expired', false) as $e) {
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
    public function hasActiveEndorsement(string $type)
    {
        return Endorsement::where('user_id', $this->id)->where('type', $type)->where('revoked', false)->where('expired', false)->exists();
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

        if ($training == null || $training->isFacilityTraining() || $training->type != 1) {
            return false;
        }

        return true;
    }

    /**
     * Return if user is visiting
     *
     * @return bool
     */
    public function isVisiting(?Area $area = null)
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
    public function isExaminer(?Area $area = null)
    {
        $query = $this->endorsements()
            ->where('type', 'EXAMINER')
            ->where('revoked', false)
            ->where('expired', false);

        if ($area === null) {
            return $query->exists();
        }

        return $query->whereHas('areas', function ($q) use ($area) {
            $q->where('areas.id', $area->id);
        })->exists();
    }

    /**
     * Return if user is a buddy
     *
     * @return bool
     */
    public function isBuddy(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', GROUP_BUDDY)->isNotEmpty();
        }

        // Check if user is buddy in the specified area
        foreach ($this->groups->where('id', GROUP_BUDDY) as $group) {
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
    public function isBuddyOrAbove(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', GROUP_BUDDY)->isNotEmpty();
        }

        // Check if user is buddy or above in the specified area
        foreach ($this->groups->where('id', '<=', GROUP_BUDDY) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is a mentor
     *
     * @return bool
     */
    public function isMentor(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', GROUP_MENTOR)->isNotEmpty();
        }

        // Check if user is mentor in the specified area
        foreach ($this->groups->where('id', GROUP_MENTOR) as $group) {
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
    public function isMentorOrAbove(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', GROUP_MENTOR)->isNotEmpty();
        }

        // Check if user is mentor or above in the specified area
        foreach ($this->groups->where('id', '<=', GROUP_MENTOR) as $group) {
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
    public function isModerator(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', GROUP_MODERATOR)->isNotEmpty();
        }

        // Check if user is moderator in the specified area
        foreach ($this->groups->where('id', GROUP_MODERATOR) as $group) {
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
    public function isModeratorOrAbove(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', GROUP_MODERATOR)->isNotEmpty();
        }

        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is moderator or above in the specified area
        foreach ($this->groups->where('id', '<=', GROUP_MODERATOR) as $group) {
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
        return $this->groups->contains('id', GROUP_ADMINISTRATOR);
    }
}
