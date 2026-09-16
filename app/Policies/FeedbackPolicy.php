<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update feedback in general, or one entry
     * in particular. An entry is governed by its computed area, so only one
     * carrying neither an explicit area nor a position falls to the
     * uncorrelated permission. Re-assigning an entry hands it to the new
     * area's staff, possibly out of the editor's own reach.
     */
    public function update(User $user, ?Feedback $feedback = null): bool
    {
        if ($feedback === null) {
            return $user->hasPermission('feedback.update');
        }

        if ($area = $feedback->area) {
            return $user->hasPermission('feedback.update', $area);
        }

        return $user->hasPermission('feedback.update')
            && $user->accessibleAreasForPermission('feedback.uncorrelated.view')->hasAccess();
    }
}
