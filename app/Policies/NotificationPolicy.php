<?php

namespace App\Policies;

use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Country;
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
        return $user->isAdmin() || ($user->isModerator() && $country->permissions()->wherePivot('user_id', $user->id)->exists());
    }
}
