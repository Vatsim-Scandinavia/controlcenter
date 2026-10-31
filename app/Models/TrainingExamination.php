<?php

namespace App\Models;

use App\Events\V1\ExaminationRecorded;
use App\Support\DomainEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingExamination extends TrainingObject
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean',
        'examination_date' => 'datetime',
    ];

    /**
     * Fire a versioned domain event when an examination is recorded, at the
     * authoritative Eloquent creation point.
     */
    protected static function booted(): void
    {
        static::created(function (self $examination): void {
            DomainEvents::dispatch(new ExaminationRecorded($examination));
        });
    }

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function examiner()
    {
        return $this->hasOne(User::class, 'id', 'examiner_id');
    }
}
