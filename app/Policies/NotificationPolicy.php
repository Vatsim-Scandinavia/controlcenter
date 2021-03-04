<?php

namespace App\Policies;

use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Area;
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
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine if the user can modify a specific area's templates
     *
     * @param User $user
     * @return bool
     */
    public function modifyAreaTemplate(User $user, Area $area)
    {
        return $user->isAdmin() || $user->isModerator($area);
    }
}
