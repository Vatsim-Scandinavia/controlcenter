<?php

namespace App\Policies;

use App\OneTimeLink;
use App\Training;
use App\TrainingReport;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TrainingReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training report.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReport  $trainingReport
     * @return mixed
     */
    public function view(User $user, TrainingReport $trainingReport)
    {
        return  $trainingReport->training->mentors->contains($user) ||
                $user->isAdmin() ||
                $user->isModerator($trainingReport->training->country) ||
                ($user->is($trainingReport->training->user) && ! $trainingReport->draft);
    }

    /**
     * Determine whether the user can create training reports.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (($key = session()->get('onetimekey')) != null) {
            $link = OneTimeLink::where('key', $key)->get()->first();

            return $link != null && $user->isMentor($link->training->country);
        }

        return $user->isMentor();
    }

    /**
     * Determine whether the user can update the training report.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReport  $trainingReport
     * @return mixed
     */
    public function update(User $user, TrainingReport $trainingReport)
    {
        return $trainingReport->training->mentors->contains($user);
    }

    /**
     * Determine whether the user can delete the training report.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReport  $trainingReport
     * @return mixed
     */
    public function delete(User $user, TrainingReport $trainingReport)
    {
        return ($user->isModerator($trainingReport->training->country) || ($user->is($trainingReport->author) && $user->isMentor($trainingReport->training->country)))
            ? Response::allow()
            : Response::deny("Only moderators and the author of the training report can delete it.");
    }
}
