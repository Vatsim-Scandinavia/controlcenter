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
     * Determine whether the user can view any of the reports related to a training
     */
    public function viewAny(User $user, Training $training): bool
    {
        return $training->mentors->contains($user) ||
                $user->is($training->user) ||
                $user->isModeratorOrAbove($training->area) ||
                $user->isAdmin();
    }

    /**
     * Determine whether the user can view the training report.
     */
    public function view(User $user, TrainingReport $trainingReport): bool
    {
        $isTrainee = $user->is($trainingReport->training->user);

        return (
            // Mentors can see all, but not drafts of their own training
            $trainingReport->training->mentors->contains($user)
            && ! ($isTrainee && $trainingReport->draft)
        )
            || $trainingReport->author->is($user) // If the user is the author of the report
            || $user->isAdmin()
            || $user->isModerator($trainingReport->training->area)
            || ($isTrainee && ! $trainingReport->draft);
    }

    /**
     * Determine whether the user can create training reports.
     */
    public function create(User $user, Training $training): bool
    {
        // If the user is the student, they cannot create a report
        if ($user->is($training->user)) {
            return false;
        }

        // Training mentors and area moderators can create a report
        if ($user->isModerator($training->area) || $training->mentors->contains($user)) {
            return true;
        }

        // Otherwise, let's see if a one-time link is used
        if (($link = OneTimeLink::getFromSession($training)) != null) {
            return $user->isMentor($link->training->area) || $user->isBuddy($link->training->area);
        }

        return false;
    }

    /**
     * Determine whether it's allowed to create a draft of the training report
     */
    public function createDraft(User $user, Training $training): bool
    {
        if (OneTimeLink::getFromSession($training) != null) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the training report.
     */
    public function update(User $user, TrainingReport $trainingReport): bool
    {
        return $trainingReport->training->mentors->contains($user) ||
                $user->isAdmin() ||
                $user->isModerator($trainingReport->training->area);
    }

    /**
     * Determine whether the user can delete the training report.
     */
    public function delete(User $user, TrainingReport $trainingReport): Response
    {
        return ($user->isAdmin() || $user->isModerator($trainingReport->training->area) || ($user->is($trainingReport->author) && $user->isMentor($trainingReport->training->area)))
            ? Response::allow()
            : Response::deny('Only moderators and the author of the training report can delete it.');
    }
}
