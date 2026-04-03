<?php

namespace App\Policies;

use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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
            $user->isAtcActive() && $user->rating >= VatsimRating::S1->value
            || $user->hasActiveEndorsement('VISITING')
            || $user->getActiveTraining(TrainingStatus::PRE_TRAINING->value) != null
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
     */
    public function bookTags(User $user): bool
    {
        return $this->bookTrainingTag($user) || $this->bookEventTag($user) || $this->bookExamTag($user);
    }

    /**
     * Determine whether the user can add training tag
     */
    public function bookTrainingTag(User $user): bool
    {
        if ($user->isModerator()) {
            return true;
        }

        return $user->rating >= VatsimRating::S1->value && $user->hasActiveTrainings(includeWaiting: false);
    }

    /**
     * Determine whether the user can add the event tag
     */
    public function bookEventTag(User $user): bool
    {
        return ($user->isMember() || $user->isVisiting()) && $user->rating >= VatsimRating::S1->value;
    }

    /**
     * Determine whether the user can add training tag
     *
     * @todo consider whether the exam tags should be stricter as normal controllers shouldn't need to use them.
     */
    public function bookExamTag(User $user): bool
    {

        return $user->isMember() && ($user->rating >= VatsimRating::C1->value || $user->isModerator());
    }

    /**
     * Determine whether the user can book this position.
     *
     * @return mixed
     */
    public function position(User $user, Booking $booking)
    {
        // TODO: Make it easier to read the order of checks
        if (($booking->position->rating->value > $user->rating || $user->rating < VatsimRating::S1->value) && ! $user->isModerator()) {
            if (
                $user->getActiveTraining(TrainingStatus::PRE_TRAINING->value) &&
                ($user->getActiveTraining()->ratings()->first()->vatsim_rating >= $booking->position->rating->value || $user->getActiveTraining()->isFacilityTraining()) &&
                $user->getActiveTraining()->area->id === $booking->position->area->id
            ) {
                return true;
            }

            return $this->deny('You are not authorized to book this position!');
        }

        return true;
    }
}
