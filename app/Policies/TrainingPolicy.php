<?php

namespace App\Policies;

use App\Training;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training.
     *
     * @param  \App\User  $user
     * @param  \App\Training  $training
     * @return mixed
     */
    public function view(User $user, Training $training)
    {
        return  $user->isMentor($training->country) ||
                $user->isModerator() ||
                $user->is($training->user);
    }

    /**
     * Determine whether the user can update the training.
     *
     * @param  \App\User  $user
     * @param  \App\Training  $training
     * @return mixed
     */
    public function update(User $user, Training $training)
    {
        return  $training->mentors->contains($user) ||
                $user->isModerator();
    }

    /**
     * Determine whether the user can delete the training.
     *
     * @param  \App\User  $user
     * @param  \App\Training  $training
     * @return mixed
     */
    public function delete(User $user, Training $training)
    {
        return $user->isModerator();
    }

    /**
     * Determines whether the user can access the training reports associated with the training
     *
     * @param User $user
     * @param Training $training
     * @return bool
     */
    public function viewReports(User $user, Training $training)
    {
        return  $user->isMentor($training->country) ||
                $user->is($training->user) ||
                $user->isAdmin();
    }

    public function createReport(User $user, Training $training)
    {
        // Check if mentor is mentoring country, not filling their own training and the training is in progress
        return $user->isMentor($training->country) && $user->isNot($training->user) && $training->status == 1;
    }

    public function createExamination(User $user, Training $training)
    {
        // Check if mentor is mentoring country, not filling their own training and the training is awaing an exam.
        return $user->isMentor($training->country) && $user->isNot($training->user) && $training->status == 2;
    }

}
