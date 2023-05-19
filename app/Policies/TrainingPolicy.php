<?php

namespace App\Policies;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Models\Area;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;

class TrainingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Training  $training
     * @return bool
     */
    public function view(User $user, Training $training)
    {
        return  $training->mentors->contains($user) ||
                $user->isModeratorOrAbove($training->area) ||
                $user->is($training->user);
    }

    /**
     * Determine whether the user can update the training.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Training  $training
     * @return bool
     */
    public function update(User $user, Training $training)
    {
        return  $training->mentors->contains($user) ||
                $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determine whether the user can delete the training.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Training  $training
     * @return bool
     */
    public function delete(User $user, Training $training)
    {
        return $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determine whether the user can close the training.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Training  $training
     * @return bool
     */
    public function close(User $user, Training $training)
    {
        return $user->is($training->user) && $training->status == 0;
    }

    /**
     * Check whether the given user is allowed to apply for training
     *
     * @param User $user
     * @return Illuminate\Auth\Access\Response
     */
    public function apply(User $user)
    {
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $divisionName = Config::get('app.owner');
        
        // Global setting if trainings are enabled
        if(!Setting::get('trainingEnabled'))
            return Response::deny("We are currently not accepting new training requests");

        // Only users within our subdivision should be allowed to apply
        if (!in_array($user->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null){
            $subdiv = "none";
            if(isset($user->subdivision)) $subdiv = $user->subdivision;
            return Response::deny("You must join {$divisionName} subdivision to apply for training. You currently belong to ".$subdiv);
        }

        // Don't accept while user waits for rating upgrade or it's been less than 7 days
        if($user->hasRecentlyCompletedTraining()){
            return Response::deny("Please wait 7 days after completed training to request a new training.");
        }

        // Not active users are forced to ask for a manual creation of refresh
        if(!$user->hasActiveTrainings(true) && $user->rating > 1 && !$user->active){
            return Response::deny("Your ATC rating is inactive in {$divisionName}");
        }

        return !$user->hasActiveTrainings(true) ? Response::allow() : Response::deny("You have an active training request");
    }

    /**
     * Check if the user has access to frontend of creationg a manual training request
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Check if the user has access to store a training manually 
     *
     * @param User $user
     * @return bool
     */
    public function store(User $user, $data)
    {

        // If user_id is not set, it's the Auth:login's user that is recorded, which we allow as people can make their own trainings
        if(!isset($data['user_id'])){
            return true;
        }

        return $user->isModeratorOrAbove(Area::find($data['training_area']));
    }

    /**
     * Check if the user has access to edit a training details
     *
     * @param User $user
     * @return bool
     */
    public function edit(User $user, Training $training)
    {
        return $user->isModeratorOrAbove($training->area);
    }

    /**
     * Determines whether the user can access the training reports associated with the training
     *
     * @param User $user
     * @param Training $training
     * @return bool
     * @deprecated Since v2.0.3. Please use Training Report policy directly.
     */
    public function viewReports(User $user, Training $training)
    {
        return $user->can('viewAny', [TrainingReport::class, $training]);
    }

    /**
     * Determines whether the user can create a training report for a given training
     *
     * @param User $user
     * @param Training $training
     * @return bool
     * @deprecated Since v2.0.3. Please use Training Report policy directly.
     */
    public function createReport(User $user, Training $training)
    {
        return $user->can('create', [TrainingReport::class, $training]);
    }

    /**
     * Determine whether the user can create a training examination for a given training
     *
     * @param User $user
     * @param Training $training
     * @return bool
     * @deprecated Since v2.0.3. Please use Training Examination policy directly.
     */
    public function createExamination(User $user, Training $training)
    {
        return $user->can('create', [TrainingExamination::class, $training]);
    }

    public function viewActiveRequests(User $user) {
        return $user->isModeratorOrAbove();
    }

    public function viewHistoricRequests(User $user) {
        return $user->isModeratorOrAbove();
    }

}
