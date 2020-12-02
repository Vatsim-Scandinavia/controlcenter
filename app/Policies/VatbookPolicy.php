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
     * @return bool
     */
    public function view()
    {
        return true;
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->rating >= 3 || $user->getActiveTraining(1) != null || $user->isModerator();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $booking
     * @return bool
     */
    public function update(User $user, Vatbook $booking)
    {
        return $booking->local_id != null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id != null;
    }

    /**
     * Determine whether the user can add tags.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function tags(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can book this position.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $booking
     * @return mixed
     */
    public function position(User $user, Vatbook $booking)
    {
        if($booking->position->rating > $user->rating && !$user->isModerator()) {
            if(($user->getActiveTraining(1) or $user->getActiveTraining(2)) && $user->getActiveTraining()->ratings()->first()->vatsim_rating == $booking->position->rating) {
                return true;
            }
            return $this->deny('You are not authorized to book this position!');
        }
        return true;
    }
}
