<?php

namespace App\Policies;

use App\Models\SweatBook;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SweatbookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view bookings.
     *
     * @return bool
     */
    public function view(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @return bool
     */
    public function update(User $user, Sweatbook $booking)
    {
        return $booking->user_id == $user->id || $user->isModeratorOrAbove();
    }
}
