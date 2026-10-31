<?php

namespace App\Events\V1;

use App\Models\Vote;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A vote was closed. Fired from the authoritative Eloquent `updated` event on
 * {@see Vote} when the `closed` flag transitions to true, covering both the
 * lazy close on view and the scheduled `clean:votes` expiry.
 */
class VoteClosed
{
    use Dispatchable;

    public function __construct(public readonly Vote $vote) {}
}
