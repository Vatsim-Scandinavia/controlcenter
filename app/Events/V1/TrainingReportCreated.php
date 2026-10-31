<?php

namespace App\Events\V1;

use App\Models\TrainingReport;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A training report was created. Fired from the authoritative Eloquent
 * `created` event on {@see TrainingReport}.
 */
class TrainingReportCreated
{
    use Dispatchable;

    public function __construct(public readonly TrainingReport $report) {}
}
