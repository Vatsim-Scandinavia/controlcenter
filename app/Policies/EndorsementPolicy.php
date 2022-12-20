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
    public function delete(User $user, Endorsement $endorsement)
    {

        // Check if the item is eligible for deletion
        if($endorsement->revoked || $endorsement->expired){
            return false;
        }

        // Check if user got correct permissions
        if($endorsement->type == 'VISITING' || $endorsement->type == 'EXAMINER'){
            return $user->isAdmin();
        }

        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can shorten endoersement date.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function shorten(User $user, Endorsement $endorsement)
    {
        return $this->delete($user, $endorsement);
    }
}
