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
