<?php

namespace App\Events\V1;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A task was created. Fired from the authoritative Eloquent `created` event on
 * {@see Task}, covering every creation path.
 */
class TaskCreated
{
    use Dispatchable;

    public function __construct(public readonly Task $task) {}
}
