<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->isModeratorOrAbove() || $user->isTeaching($model);
    }

    /**
     * Determine whether the user can view the access table.
     *
     * @return bool
     */
    public function viewAccess(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can view the reports of themselves or another user.
     *
     * @return bool
     */
    public function viewReports(User $user, User $model)
    {
        return $user->is($model) || $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return bool
     */
    public function update(User $user, User $model)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the model with that specific group
     *
     * @param  \App\Models\Group  $group
     * @return bool
     */
    public function updateGroup(User $user, User $model, Group $requstedGroup, Area $requestedArea)
    {
        // Allow admins to set all ranks from Moderator and below, and moderators can only set new mentors.
        // Only Admin can set examinators.
        return
            $this->update($user, $model) &&
            (($user->isAdmin() && $requstedGroup->id >= 2) || ($user->isModerator($requestedArea) && $requstedGroup->id == 3));
    }
}
