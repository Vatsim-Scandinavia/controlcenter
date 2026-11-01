<?php

namespace App\Models;

use App\Events\V1\ExaminationRecorded;
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
    // The ExaminationRecorded event is dispatched by the controller after the
    // examination's result and attachments are written, not from a created()
    // observer (which would fire with an unfinished record).

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function examiner()
    {
        return $this->hasOne(User::class, 'id', 'examiner_id');
    }
}
