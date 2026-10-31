<?php

namespace App\Events\V1;

use App\Helpers\TrainingStatus;
use App\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A training changed status. Fired from the authoritative Eloquent `updated`
 * event on {@see Training} whenever the `status` attribute actually changed, so
 * it covers both {@see Training::updateStatus()} and direct detail updates.
 */
class TrainingStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Training $training,
        public readonly ?TrainingStatus $previousStatus,
        public readonly TrainingStatus $newStatus,
    ) {}
}
