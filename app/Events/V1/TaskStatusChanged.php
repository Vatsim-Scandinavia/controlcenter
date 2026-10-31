<?php

namespace App\Events\V1;

use App\Helpers\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A task changed status (e.g. completed or declined). Fired from the
 * authoritative Eloquent `updated` event on {@see Task} whenever the `status`
 * attribute actually changed.
 */
class TaskStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Task $task,
        public readonly ?TaskStatus $previousStatus,
        public readonly TaskStatus $newStatus,
    ) {}
}
