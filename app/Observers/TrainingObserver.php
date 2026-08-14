<?php

namespace App\Observers;

use App\Helpers\TrainingStatus;
use App\Jobs\SyncMoodleTrainingEnrolments;
use App\Models\Training;

class TrainingObserver
{
    /**
     * Handle the Training "created" event.
     */
    public function updated(Training $training): void
    {
        if (config('services.moodle.enabled')
            && $training->wasChanged('status')
            && $training->status === TrainingStatus::PRE_TRAINING) {
            SyncMoodleTrainingEnrolments::dispatch($training);
        }
    }
}
