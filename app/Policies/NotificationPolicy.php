<?php

namespace App\Policies;

use Illuminate\Notifications\Notification;
use App\User;
use App\Country;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can modify notification templates
     *
     * @param User $user
     * @return bool
     */
    public function viewTemplates(User $user)
    {
        return $user->isModerator();
    }

    /**
     * Determine if the user can modify a specific country's templates
     *
     * @param User $user
     * @return bool
     */
    public function modifyCountryTemplate(User $user, Country $country)
    {
        return $user->isAdmin() || ($user->isModerator() && $country->training_roles->contains($user));
    }
}
