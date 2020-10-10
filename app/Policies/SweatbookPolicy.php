<?php

namespace App\Policies;

use App\User;
use App\Sweatbook;
use Illuminate\Auth\Access\HandlesAuthorization;

class SweatbookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view bookings.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Sweatbook  $booking
     * @return mixed
     */
    public function update(User $user, Sweatbook $booking)
    {
        return $booking->user_id == $user->id || $user->isModerator();
    }
}
