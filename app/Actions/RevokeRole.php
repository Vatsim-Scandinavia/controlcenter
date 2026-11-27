<?php

namespace App\Actions;

use App\Facades\DivisionApi;
use App\Models\Area;
use App\Models\User;
use App\Services\DivisionApi\DivisionApiError;
use Illuminate\Support\Facades\Gate;

class RevokeRole
{
    /**
     * Revoke $role (optionally scoped to $area) from $user on behalf of $actor.
     *
     * A Division API failure blocks the revocation, except when the API has no
     * mentor record for the user: there is nothing to remove upstream, so the
     * role is revoked locally and the result carries a warning instead.
     */
    public function __invoke(User $actor, User $user, string $role, ?Area $area): RevokeRoleResult
    {
        Gate::forUser($actor)->authorize('updateRole', [$user, $role, $area]);

        $warning = null;

        if ($role === 'mentor') {
            // Call before deleting: removeMentor()'s "last area" check counts the
            // still-present assignment row.
            $response = DivisionApi::removeMentor($user, $actor->id);

            if ($response && $response->failed()) {
                if (! DivisionApiError::isMissingRecord($response)) {
                    return RevokeRoleResult::failed(DivisionApiError::message($response));
                }

                $warning = 'Role revoked in Control Center only. ' . DivisionApi::getName()
                    . ' has no mentor record for this user, so nothing was removed there.';
            }
        }

        // Delete per-model so each revocation fires the activity-log event.
        $user->roleAssignments()
            ->where('role', $role)
            ->when($area === null,
                fn ($q) => $q->whereNull('area_id'),
                fn ($q) => $q->where('area_id', $area->id))
            ->get()
            ->each->delete();

        // Refresh the loaded relationship so hasRole() reflects the deletion.
        $user->unsetRelation('roleAssignments');

        if ($area !== null) {
            $teachesIds = $user->teaches()->where('area_id', $area->id)->pluck('id');

            if ($teachesIds->isNotEmpty() && ! $user->hasRole('mentor')) {
                $user->teaches()->detach($teachesIds->all());
            }
        }

        return RevokeRoleResult::revoked($warning);
    }
}
