<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * @todo Add a check to limit the positions that can be edited to only the ones that are in the user's area
 * @todo Introduce sector manager role that can edit positions in their sector
 * @todo Add a create rule when one exists
 */
class PositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, ?Area $area = null): bool
    {
        return $user->isModeratorOrAbove($area);
    }

    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function update(User $user, Position $position): Response
    {
        return Response::deny('You are not authorized to update this position.');
    }

    public function delete(User $user, Position $position): bool
    {
        return false;
    }

    public function create(User $user, Position $position): bool
    {
        return false;
    }

    public function createAny(User $user): bool
    {
        return false;
    }
}
