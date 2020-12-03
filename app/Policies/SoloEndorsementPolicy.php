<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoloEndorsementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view bookings.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isModerator();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isModerator();
    }
}
