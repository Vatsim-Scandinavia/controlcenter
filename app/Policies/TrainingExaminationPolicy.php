<?php

namespace App\Policies;

use App\Models\OneTimeLink;
use App\Models\TrainingExamination;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingExaminationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training examination.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingExamination  $examination
     * @return bool
     */
    public function view(User $user, TrainingExamination $examination)
    {
        return $examination->training->mentors->contains($user) || $user->is($examination->training->user);
    }

    /**
     * Determine whether the user can create training examinations.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        if (($key = session()->get('onetimekey')) != null) {
            $link = OneTimeLink::where('key', $key)->get()->first();

            return $link != null && $user->isMentorOrAbove($link->training->area);
        }

        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can update the training examination.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingExamination  $examination
     * @return bool
     */
    public function update(User $user, TrainingExamination $examination)
    {
        return $examination->draft ? ($user->isModeratorOrAbove($examination->training->area) || $user->is($examination->examiner)) : $user->isModeratorOrAbove($examination->training->area);
    }

    /**
     * Determine whether the user can delete the training examination.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingExamination  $trainingExamination
     * @return bool
     */
    public function delete(User $user, TrainingExamination $trainingExamination)
    {
        return $user->isModeratorOrAbove($trainingExamination->training->area);
    }
}
