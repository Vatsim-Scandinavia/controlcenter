<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create bookings.
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can update the task.
     *
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine if user is able to receive a task
     *
     * @return bool
     */
    public function receive(User $user)
    {
        return $user->isMentorOrAbove();
    }
}
