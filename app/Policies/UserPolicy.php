<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isModerator();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return bool
     */
    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->isModerator() || $user->isTeaching($model);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return bool
     */
    public function update(User $user, User $model)
    {
        if (!isset($model->group)) {
            return $user->isModerator();
        }

        return $user->isModerator() &&
                $user->group < $model->group;
    }

}
