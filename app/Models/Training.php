<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;

class Training extends Model
{
    use HasFactory;

    /**
     * Idle activity log options not used until LogsActivity is used.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training')
            ->logOnly(['status', 'type', 'user_id', 'paused_at', 'closed_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $guarded = [];

    protected $table = 'trainings';

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the URL to the training page.
     */
    public function path(): string
    {
        return route('training.show', ['training' => $this->id]);
    }

    /**
     * Update the status of the training, keeping the related timestamps consistent.
     *
     * @param  int  $newStatus  the new status to set
     * @param  bool  $expiredInterest  whether this update expired an interest request
     */
    public function updateStatus(int $newStatus, bool $expiredInterest = false): void
    {
        $changes = $this->resolveStatusChanges($newStatus, $expiredInterest);

        if (! empty($changes)) {
            $this->update($changes);
        }
    }

    /**
     * Resolve the attribute changes for a status transition, applying any
     * related-table side effects, without persisting the training itself.
     *
     * This lets callers batch the status change together with their own
     * attribute changes into a single save, so the activity log records one
     * entry per request rather than one per individual write.
     *
     * @return array<string, mixed>
     */
    public function resolveStatusChanges(int $newStatus, bool $expiredInterest = false): array
    {
        $oldStatus = $this->fresh()->status;

        if ($newStatus == $oldStatus) {
            return [];
        }

        $changes = ['status' => $newStatus];

        // Training was put back in queue
        if ($newStatus == 0) {
            $changes['started_at'] = null;
            $changes['closed_at'] = null;
        }

        // Training is active or completed
        if ($newStatus >= 1 || $newStatus == -1) {
            // In case someone resurrects a closed training
            if ($oldStatus < 0) {
                $changes['closed_at'] = null;
            }

            if (! isset($this->started_at)) {
                $changes['started_at'] = now();
            }

            $this->expireInterests($expiredInterest);
        }

        // Training is completed or closed
        if ($newStatus < 0) {
            $changes['closed_at'] = now();

            // Expire all related interests, as they only cause problems if the training is re-opened.
            $this->expireInterests($expiredInterest);

            // If the training is paused, sum up the length and unpause.
            if (isset($this->paused_at)) {
                $changes['paused_at'] = null;
                $changes['paused_length'] = $this->paused_length + (int) Carbon::create($this->paused_at)->diffInSeconds(Carbon::now(), true);
            }
        }

        return $changes;
    }

    /**
     * Expire all active interests for this training.
     *
     * We assume the student is contacted and interested when their training status changes positively.
     */
    protected function expireInterests(bool $expiredInterest): void
    {
        TrainingInterest::where([['training_id', $this->id], ['expired', false]])
            ->update(['updated_at' => now(), 'expired' => $expiredInterest ? 2 : 1]);
    }

    /**
     * Get an inline string of ratings associated with a training.
     */
    public function getInlineRatings(bool $vatsimRatingOnly = false): string
    {
        $ratings = $vatsimRatingOnly
            ? $this->ratings->whereNotNull('vatsim_rating')
            : $this->ratings;

        return $ratings->pluck('name')->implode(' + ');
    }

    /**
     * Get an inline string of mentors associated with a training.
     */
    public function getInlineMentors(): string
    {
        return $this->mentors->pluck('name')->implode(' & ');
    }

    /**
     * Check if training holds one or multiple MAE specific ratings.
     */
    public function isFacilityTraining(): bool
    {
        return $this->ratings->whereNull('vatsim_rating')->isNotEmpty();
    }

    /**
     * Check if training holds any VATSIM GCAP rating.
     */
    public function hasVatsimRatings(): bool
    {
        return $this->ratings->whereNotNull('vatsim_rating')->isNotEmpty();
    }

    /**
     * Get highest VATSIM GCAP rating.
     */
    public function getHighestVatsimRating(): ?Rating
    {
        return $this->ratings->whereNotNull('vatsim_rating')->sortByDesc('vatsim_rating')->first();
    }

    /**
     * Get the student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the area of the training.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the training reports for the training.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(TrainingReport::class);
    }

    /**
     * Get the examinations for the training.
     */
    public function examinations(): HasMany
    {
        return $this->hasMany(TrainingExamination::class);
    }

    /**
     * Get the ratings of the training.
     */
    public function ratings(): BelongsToMany
    {
        return $this->belongsToMany(Rating::class);
    }

    /**
     * Get the mentors for the training.
     */
    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'training_mentor')->withPivot('expire_at');
    }

    /**
     * Get training interests of this training.
     */
    public function interests(): HasMany
    {
        return $this->hasMany(TrainingInterest::class);
    }

    /**
     * Get training activities log of this training.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TrainingActivity::class);
    }

    /**
     * Get the tasks related to the training.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'subject_training_id');
    }

    /**
     * Get the one time links associated with the training.
     */
    public function onetimelink(): HasMany
    {
        return $this->hasMany(OneTimeLink::class);
    }
}
