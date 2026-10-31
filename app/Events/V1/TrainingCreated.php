<?php

namespace App\Events\V1;

use App\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new training request was created. Fired once, from the authoritative
 * Eloquent `created` event on {@see Training}, so it covers every creation path.
 */
class TrainingCreated
{
    use Dispatchable;

    public function __construct(public readonly Training $training) {}
}
