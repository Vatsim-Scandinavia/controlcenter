<?php

namespace App\Actions;

use App\Facades\DivisionApi;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class GrantRole
{
    /**
     * Grant $role (optionally scoped to $area) to $user on behalf of $actor.
     *
     * @return string|null Error message on Division API failure, otherwise null.
     */
    public function __invoke(User $actor, User $user, string $role, ?Area $area): ?string
    {
        Gate::forUser($actor)->authorize('updateRole', [$user, $role, $area]);

        if ($role === 'mentor') {
            $response = DivisionApi::assignMentor($user, $actor->id);

            if ($response && $response->failed()) {
                return 'Request failed due to error in ' . DivisionApi::getName()
                    . ' API: ' . $response->json()['message'];
            }
        }

        $user->roleAssignments()->firstOrCreate([
            'role' => $role,
            'area_id' => $area?->id,
        ]);

        return null;
    }
}
