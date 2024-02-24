<?php

namespace App\Tasks\Types;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Http\Controllers\TrainingActivityController;
use App\Models\Task;
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
        // Show the selected rating if set
        if ($model->subjectTrainingRating) {
            return 'Upgrade rating to ' . $model->subjectTrainingRating->name;
        } else {
            return 'Upgrade rating to ' . $model->subjectTraining->getInlineRatings(true);
        }
    }

    public function getLink(Task $model)
    {
        $training = $model->subjectTraining;
        $user = $model->subject;
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
        $training = $model->subjectTraining;
        $user = $model->subject;

        if ($training->hasVatsimRatings()) {

            // Call the Division API to request the upgrade
            $rating = $model->subjectTrainingRating ? $model->subjectTrainingRating : $training->getHighestVatsimRating();
            $response = DivisionApi::requestRatingUpgrade($user, $rating, Auth::id());
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

    public function requireRatingSelection()
    {
        return true;
    }

    public function isApproval()
    {
        return Setting::get('divisionApiEnabled', false);
    }
}
