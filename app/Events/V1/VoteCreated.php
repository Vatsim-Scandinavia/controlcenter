<?php

namespace App\Events\V1;

use App\Models\Vote;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A vote was created. Fired from the authoritative Eloquent `created` event on
 * {@see Vote}.
 */
class VoteCreated
{
    use Dispatchable;

    public function __construct(public readonly Vote $vote) {}
}
