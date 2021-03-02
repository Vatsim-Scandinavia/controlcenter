<?php

namespace App\Policies;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TrainingReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training report.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingReport  $trainingReport
     * @return bool
     */
    public function view(User $user, TrainingReport $trainingReport)
    {
        return  $trainingReport->training->mentors->contains($user) ||
                $user->isAdmin() ||
                $user->isModerator($trainingReport->training->area) ||
                ($user->is($trainingReport->training->user) && ! $trainingReport->draft);
    }

    /**
     * Determine whether the user can create training reports.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        if (($key = session()->get('onetimekey')) != null) {
            $link = OneTimeLink::where('key', $key)->get()->first();

            return $link != null && $user->isMentor($link->training->area);
        }

        return $user->isMentor();
    }

    /**
     * Determine whether the user can update the training report.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingReport  $trainingReport
     * @return bool
     */
    public function update(User $user, TrainingReport $trainingReport)
    {
        return  $trainingReport->training->mentors->contains($user) ||
                $user->isAdmin() ||
                $user->isModerator($trainingReport->training->area);
    }

    /**
     * Determine whether the user can delete the training report.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingReport  $trainingReport
     * @return Illuminate\Auth\Access\Response
     */
    public function delete(User $user, TrainingReport $trainingReport)
    {
        return ($user->isAdmin() || $user->isModerator($trainingReport->training->area) || ($user->is($trainingReport->author) && $user->isMentor($trainingReport->training->area)))
            ? Response::allow()
            : Response::deny("Only moderators and the author of the training report can delete it.");
    }
}
