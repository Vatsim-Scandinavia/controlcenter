<?php

namespace App\Actions;

use App\Facades\DivisionApi;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RevokeRole
{
    /**
     * Revoke $role (optionally scoped to $area) from $user on behalf of $actor.
     *
     * @return string|null Error message on Division API failure, otherwise null.
     */
    public function __invoke(User $actor, User $user, string $role, ?Area $area): ?string
    {
        Gate::forUser($actor)->authorize('updateRole', [$user, $role, $area]);

        if ($role === 'mentor') {
            // Call before deleting: removeMentor()'s "last area" check counts the
            // still-present assignment row.
            $response = DivisionApi::removeMentor($user, $actor->id);

            if ($response && $response->failed()) {
                return 'Request failed due to error in ' . DivisionApi::getName()
                    . ' API: ' . $response->json()['message'];
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

        return null;
    }
}
