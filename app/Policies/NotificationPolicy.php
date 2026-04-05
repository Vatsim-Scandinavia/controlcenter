<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can modify notification templates
     *
     * @return bool
     */
    public function viewTemplates(User $user)
    {
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can modify a specific area's templates
     *
     * @return bool
     */
    public function modifyAreaTemplate(User $user, Area $area)
    {
        return $user->hasRole('admin') || $user->hasRole('moderator', $area);
    }
}
