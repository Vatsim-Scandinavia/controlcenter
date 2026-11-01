<?php

namespace App\Events\V1;

use App\Models\TrainingExamination;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A training examination (exam report) was recorded. Fired from the
 * authoritative Eloquent `created` event on {@see TrainingExamination}.
 */
class ExaminationRecorded
{
    use Dispatchable;

    public function __construct(public readonly TrainingExamination $examination) {}
}
