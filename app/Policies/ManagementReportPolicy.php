<?php

namespace App\Policies;

use App\Models\ManagementReport;
use App\Models\User;
use App\Models\Area;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagementReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function accessTrainingReports(User $user, $filterArea) {

        if($filterArea){
            return $user->isModeratorOrAbove(Area::find($filterArea));
        }

        return $user->isAdmin();
        
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewMentors(User $user) {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAccessReport(User $user) {
        return $user->isModeratorOrAbove();
    }
}
