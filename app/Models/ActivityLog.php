<?php

namespace App\Models;

use App\Contracts\ResolvesActivitySubjectRoute;
use App\Helpers\ActivityLevel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * Application activity log entry.
 *
 * Extends the spatie/laravel-activitylog model and adds a severity level plus the
 * request context (IP address and user agent) of the action that produced the entry.
 *
 * @property ActivityLevel $level
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property-read string|null $subject_route
 */
class ActivityLog extends BaseActivity
{
    /**
     * Use the same database table convention as established.
     */
    protected $table = 'activity_logs';

    /**
     * Mirror the database default so a level is always present before persistence.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'level' => 'info',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'level' => ActivityLevel::class,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            // System/console actions have no meaningful client context.
            if (app()->runningInConsole()) {
                return;
            }

            $activity->ip_address ??= request()->ip();
            $activity->user_agent ??= request()->userAgent();
        });
    }

    /**
     * URL to the subject's detail page, or null when the subject type has no
     * web show route (rendered as plain text by the admin view).
     */
    public function getSubjectRouteAttribute(): ?string
    {
        if ($this->subject_id === null) {
            return null;
        }

        // A subject model (including one shipped by a module) may resolve its
        // own detail URL, keeping this view free of per-subject knowledge.
        if ($this->subject_type !== null) {
            $class = Relation::getMorphedModel($this->subject_type) ?? $this->subject_type;

            if (is_string($class) && is_a($class, ResolvesActivitySubjectRoute::class, true)) {
                return $class::activitySubjectRoute($this->subject_id);
            }
        }

        return match ($this->subject_type) {
            Training::class => route('training.show', $this->subject_id),
            User::class => route('user.show', $this->subject_id),
            default => null,
        };
    }
}
