<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    const CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE = 'continued_interest_notification_log';

    protected $guarded = [];

    protected $dates = [
        'started_at',
        'finished_at'
    ];

    /**
     * Get the URL to the training page
     *
     * @return string
     */
    public function path()
    {
        return route('training.show', ['training' => $this->id]);
    }

    /**
     * Update the status of the training.
     * This method will make sure that when updating the status the training
     * that the timestamps are also correctly updated.
     *
     * @param int $status
     */
    public function updateStatus(int $status)
    {
        $oldStatus = $this->fresh()->status;

        if (($status == 0 || $status == -1) && $status < $oldStatus) {
            // Training was set back in queue
            $this->update(['started_at' => null, 'finished_at' => null]);
        }

        if ($status == 1) {
            if ($status > $oldStatus) {
                // Training has begun
                $this->update(['started_at' => now()]);
            } elseif ($status < $oldStatus) {
                $this->update(['finished_at' => null]);
            }
        }

        if ($status == 3 && $status > $oldStatus) {
            if ($this->started_at == null) {
                $this->update(['started_at' => now(), 'finished_at' => now()]);
            } else {
                $this->update(['finished_at' => now()]);
            }
        }

        $this->update(['status' => $status]);
    }

    /**
     * Get the student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country of the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the training reports for the training.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(TrainingReport::class);
    }

    /**
     * Get the ratings of the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    /**
     * Get the mentors for the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mentors()
    {
        return $this->belongsToMany(User::class)->withPivot('expire_at');
    }
}
