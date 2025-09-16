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
 */
class PositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isModeratorOrAbove();
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
        return $user->isModeratorOrAbove($position->area)
            ? Response::allow()
            : Response::deny('You are not authorized to update this position.');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->isModeratorOrAbove($position->area);
    }

    public function create(User $user, Position $position): bool
    {
        $area = Area::findOrFail($position->area_id);

        return $user->isModeratorOrAbove($area);
    }
}
