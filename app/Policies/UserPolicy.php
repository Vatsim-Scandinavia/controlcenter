<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;
use App\Models\Area;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->isModeratorOrAbove() || $user->isTeaching($model);
    }

    /**
     * Determine whether the user can view the access table.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAccess(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user, User $model)
    {
        return $user->isModeratorOrAbove();    
    }

    /**
     * Determine whether the user can update the visiting status
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function updateVisiting(User $user)
    {
        return $user->isAdmin();    
    }

    /**
     * Determine whether the user can update the model with that specific group
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return bool
     */
    public function updateGroup(User $user, User $model, Group $requstedGroup, Area $requestedArea)
    {
        // Allow admins to set all ranks from Moderator and below, and moderators can only set new mentors.
        // Only Admin can set examinators.
        return
            $this->update($user, $model) &&
            (($user->isAdmin() && $requstedGroup->id >= 2) || ($user->isModerator($requestedArea) && $requstedGroup->id == 3))
        ;
    }

}
