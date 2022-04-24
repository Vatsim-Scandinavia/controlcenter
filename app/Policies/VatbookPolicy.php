<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vatbook;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Config;

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

        // vRoute booking
        if($booking->local_id == null){
            return $this->deny('This booking must be changed in vRoute or service it was booked from');
        }

        // Discord booking
        if($booking->source == "DISCORD"){
            return $this->deny('This booking must be changed in Discord where it was booked');
        }

        // The user is the owner of booking
        if($booking->cid == $user->id){
            return true;
        }

        // The booking is not vRoute/Discord but the user is moderator or above
        if($booking->local_id != null && $booking->source != "DISCORD" && $user->isModeratorOrAbove()){
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can add any tags
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function bookTags(User $user)
    {
        return $this->bookTrainingTag($user) || $this->bookEventTag($user) || $this->bookExamTag($user);
    }

    /**
     * Determine whether the user can add training tag
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function bookTrainingTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 3) || $user->visiting_controller == true;
    }

    /**
     * Determine whether the user can add training tag
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function bookEventTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 3) || $user->visiting_controller == true;
    }

    /**
     * Determine whether the user can add training tag
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function bookExamTag(User $user)
    {
        return ($user->subdivision == Config::get('app.owner_short') && $user->rating >= 5) || $user->isModerator();
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
            if($user->getActiveTraining(1) &&
                ($user->getActiveTraining()->ratings()->first()->vatsim_rating >= $booking->position->rating || $user->getActiveTraining()->isMaeTraining()) &&
                $user->getActiveTraining()->area->id === $booking->position->area) {
                return true;
            }
            return $this->deny('You are not authorized to book this position!');
        }
        return true;
    }
}
