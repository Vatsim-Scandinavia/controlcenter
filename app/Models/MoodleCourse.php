<?php

namespace App\Models;

use Database\Factories\MoodleCourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoodleCourse extends Model
{
    /** @use HasFactory<MoodleCourseFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(MoodleCourseRule::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(MoodleEnrolment::class);
    }
}
