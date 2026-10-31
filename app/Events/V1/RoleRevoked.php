<?php

namespace App\Events\V1;

use App\Models\RoleAssignment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A role was revoked from a user. Fired from the authoritative Eloquent
 * `deleted` event on {@see RoleAssignment}. Every revocation deletes per model
 * (not a mass query-builder delete), so this covers all revoke paths.
 */
class RoleRevoked
{
    use Dispatchable;

    public function __construct(public readonly RoleAssignment $assignment) {}
}
