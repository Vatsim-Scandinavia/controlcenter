<?php

namespace App\Policies;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\Training;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TrainingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training.
     *
     * @return bool
     */
    public function view(User $user, Training $training)
    {
        return $training->mentors->contains($user) ||
                $user->isModeratorOrAbove($training->area) ||
                $user->is($training->user);
    }

    /**
     * Determine whether the user can update the training.
     *
     * @return bool
     */
    public function update(User $user, Training $training)
    {
        return $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determine whether the user can delete the training.
     *
     * @return bool
     */
    public function delete(User $user, Training $training)
    {
        return $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determine whether the user can close the training.
     *
     * @return bool
     */
    public function close(User $user, Training $training)
    {
        return $user->is($training->user) && $training->status == TrainingStatus::IN_QUEUE->value;
    }

    /**
     * Determine whether the user can mark their pre-training as completed.
     *
     * @return bool
     */
    public function togglePreTrainingCompleted(User $user, Training $training)
    {
        return $training->status == TrainingStatus::PRE_TRAINING->value &&
                ($training->pre_training_completed == false || $user->isModeratorOrAbove($training->area));
    }

    /**
     * Check whether the given user is allowed to apply for training
     *
     * @return Illuminate\Auth\Access\Response
     */
    public function apply(User $user)
    {
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $divisionName = config('app.owner_name_short');

        // Global setting if trainings are enabled
        if (! Setting::get('trainingEnabled')) {
            return Response::deny('We are currently not accepting new training requests');
        }

        // Only users within our subdivision should be allowed to apply
        if (! in_array($user->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null) {
            $subdiv = 'none';
            if (isset($user->subdivision)) {
                $subdiv = $user->subdivision;
            }

            return Response::deny("You must join {$divisionName} to apply for training. You currently belong to " . $subdiv);
        }

        // Don't accept while user waits for rating upgrade or it's been less than 7 days
        if ($user->hasRecentlyCompletedTraining()) {
            return Response::deny('Please wait 7 days after completed training to request a new training.');
        }

        // Not active users are forced to ask for a manual creation of refresh
        if (! $user->hasActiveTrainings(true) && $user->rating > 1 && ! $user->isAtcActive()) {
            return Response::deny("Your ATC rating is inactive in {$divisionName}");
        }

        return ! $user->hasActiveTrainings(true) ? Response::allow() : Response::deny('You have an active training request');
    }

    /**
     * Check if the user has access to frontend of creationg a manual training request
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Check if the user has access to store a training manually
     *
     * @return bool
     */
    public function store(User $user, $data)
    {
        // If user_id is not set, it's the Auth:login's user that is recorded, which we allow as people can make their own trainings
        if (! isset($data['user_id'])) {
            return true;
        }

        return $user->isModeratorOrAbove(Area::find($data['training_area']));
    }

    /**
     * Check if the user has access to edit a training details
     *
     * @return bool
     */
    public function edit(User $user, Training $training)
    {
        return $user->isModeratorOrAbove($training->area);
    }

    public function viewActiveRequests(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    public function viewHistoricRequests(User $user)
    {
        return $user->isModeratorOrAbove();
    }
}
