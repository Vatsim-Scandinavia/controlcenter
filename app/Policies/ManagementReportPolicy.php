<?php

namespace App\Policies;

use App\ManagementReport;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagementReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function accessTrainingReports(User $user) {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewMentors(User $user) {
        return $user->isAdmin() ||
            $user->isModerator();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAtcActivity(User $user) {
        return $user->isAdmin();
    }
}
