<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Endorsement;
use Illuminate\Auth\Access\HandlesAuthorization;

class EndorsementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view endorsements details.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user == Auth::user() || $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can create endorsements.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user, $type = null)
    {
        if($type == 'VISITING' || $type == 'EXAMINER'){
            return $user->isAdmin();
        }

        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can revoke endoersements.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user, Endorsement $endosement)
    {
        if($endosement->type == 'VISITING' || $endosement->type == 'EXAMINER'){
            return $user->isAdmin();
        }

        return $user->isModeratorOrAbove();
    }
}
