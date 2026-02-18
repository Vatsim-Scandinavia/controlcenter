<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the feedback.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Feedback  $feedback
     * @return bool
     */
    public function update(User $user, Feedback $feedback)
    {
        return $user->isModeratorOrAbove();
    }
}
