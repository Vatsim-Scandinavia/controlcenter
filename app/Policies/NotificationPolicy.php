<?php

namespace App\Policies;

use Illuminate\Notifications\Notification;
use App\User;
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
    public function modifyTemplates(User $user) {
        return $user->isAdmin();
    }
}
