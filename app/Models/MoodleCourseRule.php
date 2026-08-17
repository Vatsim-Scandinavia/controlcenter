<?php

namespace App\Models;

use Database\Factories\MoodleCourseRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodleCourseRule extends Model
{
    /** @use HasFactory<MoodleCourseRuleFactory> */
    use HasFactory;

    protected $guarded = [];

    public function course(): BelongsTo
    {
        return $this->belongsTo(MoodleCourse::class, 'moodle_course_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }
}
