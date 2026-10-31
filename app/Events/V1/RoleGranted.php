<?php

namespace App\Events\V1;

use App\Models\RoleAssignment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A role was granted to a user. Fired from the authoritative Eloquent `created`
 * event on {@see RoleAssignment}, the single point every grant path funnels
 * through.
 */
class RoleGranted
{
    use Dispatchable;

    public function __construct(public readonly RoleAssignment $assignment) {}
}
