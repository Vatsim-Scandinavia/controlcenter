<?php

namespace App\Policies;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingObject;
use App\Models\TrainingReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OneTimeLinkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create one time links.
     *
     * @param  \App\Models\User $user
     * @param Training $training
     * @param string $type
     * @return bool
     */
    public function create(User $user, Training $training, string $type)
    {
        // Only allow examination link generation if the training is awaiting exam
        if ($type == OneTimeLink::TRAINING_EXAMINATION_TYPE){
            return $training->status == 3 && ($training->mentors->contains($user) || $user->isModeratorOrAbove($training->area));
        }
        
        return $training->mentors->contains($user) || $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determine whether the user can access the link
     *
     * @param User $user
     * @param OneTimeLink $link
     * @return bool
     */
    public function access(User $user, OneTimeLink $link)
    {
        return ($link->reportType() && $user->isMentor()) || ($link->examinationType() && $user->isExaminer());
    }

}
