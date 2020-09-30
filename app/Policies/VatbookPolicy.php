<?php

namespace App\Policies;

use App\User;
use App\Vatbook;
use Illuminate\Auth\Access\HandlesAuthorization;

class VatbookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view bookings.
     *
     * @return mixed
     */
    public function view()
    {
        return true;
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->rating >= 3 || $user->getActiveTraining(2) != null || $user->isModerator();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $booking
     * @return mixed
     */
    public function update(User $user, Vatbook $booking)
    {
        return $booking->local_id != null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id != null;
    }
}
