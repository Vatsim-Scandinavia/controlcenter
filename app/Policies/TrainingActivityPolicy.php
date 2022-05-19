<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Training;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create training comment.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function comment(User $user, Training $training)
    {
        return $user->can('update', [Training::class, $training]);
    }

    /**
     * Determine whether the user can view training activity.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function view(User $user, Training $training, string $type)
    {
        if($type == 'COMMENT'){
            return  $training->mentors->contains($user) || $user->isModeratorOrAbove($training->area);
        }

        return true;
    }
}