<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, ?Area $area = null): bool
    {
        return $user->hasPermission('manage-positions', $area);
    }

    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function update(User $user, Position $position): Response
    {
        return $user->hasPermission('manage-positions', $position->area)
            ? Response::allow()
            : Response::deny('You are not authorized to update this position.');
    }

    public function delete(User $user, Position $position): Response
    {
        return $user->hasPermission('manage-positions', $position->area)
            ? Response::allow()
            : Response::deny('You are not authorized to delete this position.');
    }

    public function create(User $user, Position $position): Response
    {
        // Resolve the area from area_id explicitly: $position->area is unreliable on
        // unsaved models (e.g. when authorising a create with a transient Position).
        $area = Area::find($position->area_id);

        return $user->hasPermission('manage-positions', $area)
            ? Response::allow()
            : Response::deny('You are not authorized to create positions in this area.');
    }

    public function createAny(User $user): Response
    {
        return $user->hasPermission('manage-positions')
            ? Response::allow()
            : Response::deny('You are not authorized to create positions.');
    }
}
