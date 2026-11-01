<?php

namespace App\Events\V1;

use App\Models\Endorsement;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An endorsement was revoked. Fired from the authoritative Eloquent `updated`
 * event on {@see Endorsement} when the `revoked` flag transitions to true.
 */
class EndorsementRevoked
{
    use Dispatchable;

    public function __construct(public readonly Endorsement $endorsement) {}
}
