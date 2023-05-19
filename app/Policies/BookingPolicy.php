<?php

namespace App\Policies;

use App\Helpers\VatsimRating;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Config;

class BookingPolicy
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
     * @return bool
     */
    public function create(User $user)
    {
        return
            $user->active && $user->rating >= VatsimRating::S2->value
            || $user->active && $user->rating >= VatsimRating::S1->value && $user->hasActiveEndorsement('S1', true)
            || $user->getActiveTraining(1) != null
            || $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @return bool
     */
    public function update(User $user, Booking $booking): Response
    {
        // Discord booking
        if ($booking->source == 'DISCORD') {
            return Response::deny('This booking must be changed in Discord where it was booked');
        }

        // The user is the owner of booking
        if ($booking->user_id == $user->id) {
            return Response::allow();
        }

        // The booking is not Discord but the user is moderator or above
        if ($booking->source != 'DISCORD' && $user->isModeratorOrAbove()) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can add any tags
     *
     * @return bool
     */
    public function bookTags(User $user)
    {
        return $this->bookTrainingTag($user) || $this->bookEventTag($user) || $this->bookExamTag($user);
    }

    /**
     * Determine whether the user can add training tag
     *
     * @return bool
     */
    public function bookTrainingTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 3) || $user->isVisiting();
    }

    /**
     * Determine whether the user can add training tag
     *
     * @return bool
     */
    public function bookEventTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 3) || $user->isVisiting();
    }

    /**
     * Determine whether the user can add training tag
     *
     * @return bool
     */
    public function bookExamTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 5) || $user->isModerator();
    }

    /**
     * Determine whether the user can book this position.
     *
     * @return mixed
     */
    public function position(User $user, Booking $booking)
    {
        // TODO: Make it easier to read the order of checks
        if (($booking->position->rating > $user->rating || $user->rating < VatsimRating::S1->value) && ! $user->isModerator()) {
            if (
                $user->getActiveTraining(1) &&
                ($user->getActiveTraining()->ratings()->first()->vatsim_rating >= $booking->position->rating || $user->getActiveTraining()->isMaeTraining()) &&
                $user->getActiveTraining()->area->id === $booking->position->area
            ) {
                return true;
            }

            return $this->deny('You are not authorized to book this position!');
        }

        return true;
    }
}
