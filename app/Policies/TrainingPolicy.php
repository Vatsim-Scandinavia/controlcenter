<?php

namespace App\Policies;

use App\Training;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use anlutro\LaravelSettings\Facade as Setting;

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
     * Check whether the given user is allowed to apply for training
     *
     * @param User $user
     * @return bool
     */
    public function apply(User $user)
    {
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        
        if (!in_array($user->handover->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null)
            return Response::deny("You must join Scandinavia subdivision to apply for training");

        return !$user->hasActiveTrainings() ? Response::allow() : Response::deny("You already have a pending training request");
    }

    /**
     * Check if the user has access to create a training manually
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
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
        return $user->isMentor($training->country) && $user->isNot($training->user);
    }

    public function createExamination(User $user, Training $training)
    {
        // Check if mentor is mentoring country, not filling their own training and the training is awaing an exam.
        return $user->isMentor($training->country) && $user->isNot($training->user);
    }

}
