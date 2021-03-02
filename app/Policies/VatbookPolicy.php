<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vatbook;
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
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->rating >= 3 || $user->getActiveTraining(1) != null || $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vatbook  $booking
     * @return bool
     */
    public function update(User $user, Vatbook $booking)
    {
        return $booking->local_id != null && $booking->cid == $user->id || $user->isModeratorOrAbove() && $booking->local_id != null;
    }

    /**
     * Determine whether the user can add tags.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function tags(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can book this position.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vatbook  $booking
     * @return mixed
     */
    public function position(User $user, Vatbook $booking)
    {
        if(($booking->position->rating > $user->rating || $user->rating < 3) && !$user->isModerator()) {
            if($user->getActiveTraining(1) && $user->getActiveTraining()->ratings()->first()->vatsim_rating >= $booking->position->rating && $user->getActiveTraining()->country->id === $booking->position->country) {
                return true;
            }
            return $this->deny('You are not authorized to book this position!');
        }
        return true;
    }
}
