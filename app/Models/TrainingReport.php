<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingReport extends TrainingObject
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean',
        'report_date' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Stamp the publishing date the first time a report becomes non-draft.
     *
     * Keeping this on the model makes it the single source of truth, so the
     * date is set correctly regardless of which code path publishes.
     */
    protected static function booted(): void
    {
        static::saving(function (self $report): void {
            if (! $report->draft && $report->published_at === null) {
                $report->published_at = now();
            }
        });
    }

    /**
     * The date this report should be sorted and displayed by in activity feeds.
     */
    public function getActivityDateAttribute()
    {
        return $this->published_at ?? $this->created_at;
    }

    public function path()
    {
        return route('training.report.edit', ['report' => $this->id]);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'written_by_id');
    }
}
