<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagementReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can access the training reports
     *
     * @return bool
     */
    public function accessTrainingReports(User $user, $filterArea)
    {
        if ($filterArea) {
            return $user->hasPermission('view-training-statistics', Area::find($filterArea));
        }

        return $user->hasPermission('view-training-statistics');
    }

    /**
     * Determine whether the user can access the training activities report
     *
     * @return bool
     */
    public function accessActivityReports(User $user, $filterArea)
    {
        if ($filterArea) {
            return $user->hasPermission('view-training-activities', Area::find($filterArea));
        }

        return $user->hasPermission('view-training-activities');
    }

    /**
     * Determine whether the user see the training report index
     *
     * @return bool
     */
    public function viewMentors(User $user)
    {
        return $user->hasPermission('view-management-reports');
    }

    /** Determine whether the user can see the feedback index
     *
     */
    public function viewFeedback(User $user): bool
    {
        return $user->accessibleAreasForPermission('view-correlated-feedback')->hasAccess();
    }

    /**
     * Determine whether the user can see the access report
     *
     * @return bool
     */
    public function viewAccessReport(User $user)
    {
        return $user->hasPermission('view-management-reports');
    }
}
