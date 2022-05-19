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
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingExamination  $examination
     * @return bool
     */
    public function view(User $user, TrainingExamination $examination)
    {
        return $user->isModeratorOrAbove() || ($examination->training->mentors->contains($user) || $user->is($examination->training->user) || $user->isExaminer());
    }

    /**
     * Determine whether the user can create training examinations.
     *
     * @param \App\Models\User $user
     * @param Training $training
     * @return bool
     */
    public function create(User $user, Training $training)
    {
        if (($link = $this->getOneTimeLink($training)) != null) {
            return $user->isExaminer($link->training->area) && $user->isNot($training->user);
        }

        // Check if mentor is examiner in the area, not filling their own training and the training is awaiting an exam.
        return $user->isExaminer() && $user->isNot($training->user);
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
        return $examination->draft ? ($user->isModeratorOrAbove($examination->training->area) || $user->isExaminer()) : $user->isModeratorOrAbove($examination->training->area);
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

    /**
     * Get the one time link from a session given a training
     *
     * @param $training
     * @return null
     */
    private function getOneTimeLink($training) {
        $link = null;

        $key = session()->get('onetimekey');

        if ($key != null) {
            $link = OneTimeLink::where([
                ['training_id', '=', $training->id],
                ['key', '=', $key]
            ])->get()->first();
        }

        return $link;
    }
}
