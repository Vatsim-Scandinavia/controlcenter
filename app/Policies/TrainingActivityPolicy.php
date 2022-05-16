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
}
