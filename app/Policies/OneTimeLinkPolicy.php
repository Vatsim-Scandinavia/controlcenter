<?php

namespace App\Policies;

use App\OneTimeLink;
use App\Training;
use App\TrainingExamination;
use App\TrainingObject;
use App\TrainingReport;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OneTimeLinkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create one time links.
     *
     * @param \App\User $user
     * @param Training $training
     * @param string $type
     * @return bool
     */
    public function create(User $user, Training $training, string $type)
    {
        if ($type === OneTimeLink::TRAINING_REPORT_TYPE)
            return $training->mentors->contains($user);

        return $user->isModerator($training->country);
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
        return $user->rating >= 3 && ($user->subdivision == "SCA" || $user->visiting_controller);
    }

}
