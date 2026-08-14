<?php

namespace App\Models;

use Database\Factories\MoodleEnrolmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodleEnrolment extends Model
{
    /** @use HasFactory<MoodleEnrolmentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * @return BelongsTo<MoodleCourse, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(MoodleCourse::class, 'moodle_course_id');
    }

    public function userLink(): BelongsTo
    {
        return $this->belongsTo(MoodleUserLink::class, 'moodle_user_link_id');
    }
}
