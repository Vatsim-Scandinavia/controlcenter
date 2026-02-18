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
     * @return bool
     */
    public function update(User $user, Feedback $feedback)
    {
        return $user->isModeratorOrAbove();
    }
}
