<?php

namespace App\Tasks\Types;

use App\Facades\DivisionApi;
use App\Http\Controllers\TrainingActivityController;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RatingUpgrade extends Types
{
    public function getName()
    {
        return 'Rating Upgrade';
    }

    public function getIcon()
    {
        return 'fa-circle-arrow-up';
    }

    public function getText(Task $model)
    {
        return 'Upgrade rating to ' . Training::find($model->subject_training_id)->getInlineRatings(false);
    }

    public function getLink(Task $model)
    {
        $training = Training::find($model->subject_training_id);
        $user = User::find($model->subject_user_id);
        $userEud = $user->division == 'EUD';

        if ($userEud && ! $training->hasVatsimRatings()) {
            return route('endorsements.create.id', $user->id);
        }

        return false;
    }

    public function create(Task $model)
    {
        parent::onCreated($model);
    }

    public function complete(Task $model)
    {
        // If the training requires a VATSIM GCAP upgrade, create a comment on the training
        $training = Training::find($model->subject_training_id);
        $user = User::find($model->subject_user_id);

        if ($training->hasVatsimRatings()) {

            // Call the Division API to request the upgrade
            $response = DivisionApi::requestRatingUpgrade($user, $training->getHighestVatsimRating(), Auth::id());
            if ($response && $response->failed()) {
                return 'Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message'];
            }

            // Log in training activity
            TrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Rating upgrade requested.');
        }

        // Run the parent method
        parent::onCompleted($model);
    }

    public function decline(Task $model)
    {
        parent::onDeclined($model);
    }

    public function showConnectedRatings()
    {
        return true;
    }

    public function allowNonVatsimRatings()
    {
        return false;
    }
}
