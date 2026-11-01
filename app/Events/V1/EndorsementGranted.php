<?php

namespace App\Events\V1;

use App\Models\Endorsement;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An endorsement was granted. Fired from the authoritative Eloquent `created`
 * event on {@see Endorsement}, covering both manual grants and training
 * completion.
 */
class EndorsementGranted
{
    use Dispatchable;

    public function __construct(public readonly Endorsement $endorsement) {}
}
