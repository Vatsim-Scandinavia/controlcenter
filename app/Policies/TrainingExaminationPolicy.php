<?php

namespace App\Policies;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingExaminationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training examination.
     *
     * @return bool
     */
    public function view(User $user, TrainingExamination $examination)
    {
        return $user->hasRole(['admin', 'moderator']) || ($examination->training->mentors->contains($user) || $user->is($examination->training->user) || $user->isExaminer());
    }

    /**
     * Determine whether the user can create training examinations.
     *
     * @return bool
     */
    public function create(User $user, Training $training)
    {

        if ($user->hasRole('admin')) {
            return true;
        }

        // If one-time link is used
        if (($link = OneTimeLink::getFromSession($training)) != null) {
            return $user->isExaminer($link->training->area) && $user->isNot($training->user);
        }

        // If otl not used or invalid, use the normal check
        // Check if mentor is examiner in the area, not filling their own training.
        return $user->isExaminer($training->area) && $user->isNot($training->user);
    }

    /**
     * Determine whether the user can update the training examination.
     *
     * @return bool
     */
    public function update(User $user, TrainingExamination $examination)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $examination->draft ? ($user->hasRole(['admin', 'moderator'], $examination->training->area) || $user->isExaminer()) : $user->hasRole(['admin', 'moderator'], $examination->training->area);
    }

    /**
     * Determine whether the user can delete the training examination.
     *
     * @return bool
     */
    public function delete(User $user, TrainingExamination $trainingExamination)
    {
        return $user->hasRole(['admin', 'moderator'], $trainingExamination->training->area) || $user->hasRole('admin');
    }
}
