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
        return $user->isModeratorOrAbove() || ($examination->training->mentors->contains($user) || $user->is($examination->training->user) || $user->isExaminer());
    }

    /**
     * Determine whether the user can create training examinations.
     *
     * @return bool
     */
    public function create(User $user, Training $training)
    {

        if ($user->isAdmin()) {
            return true;
        }

        // If one-time link is used
        if (($link = $this->getOneTimeLink($training)) != null) {
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
        if ($user->isAdmin()) {
            return true;
        }

        return $examination->draft ? ($user->isModeratorOrAbove($examination->training->area) || $user->isExaminer()) : $user->isModeratorOrAbove($examination->training->area);
    }

    /**
     * Determine whether the user can delete the training examination.
     *
     * @return bool
     */
    public function delete(User $user, TrainingExamination $trainingExamination)
    {
        return $user->isModeratorOrAbove($trainingExamination->training->area) || $user->isAdmin();
    }

    /**
     * Get the one time link from a session given a training
     *
     * @return null
     */
    private function getOneTimeLink($training)
    {
        $link = null;

        $key = session()->get('onetimekey');

        if ($key != null) {
            $link = OneTimeLink::where([
                ['training_id', '=', $training->id],
                ['key', '=', $key],
            ])->get()->first();
        }

        return $link;
    }
}
