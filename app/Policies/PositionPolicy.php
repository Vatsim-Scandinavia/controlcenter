<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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

    public function update(User $user, Position $position): bool
    {
        info($position);
        return $user->isModeratorOrAbove($position->area);
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
