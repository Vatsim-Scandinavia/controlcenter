<?php

namespace App\Tasks\Types;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Http\Controllers\TrainingActivityController;
use App\Models\Task;
use App\Notifications\TrainingCustomNotification;
use Illuminate\Support\Facades\Auth;

class TheoreticalExam extends Types
{
    public function getName()
    {
        return 'Theoretical Exam Access';
    }

    public function getIcon()
    {
        return 'fa-key';
    }

    public function getText(Task $model)
    {
        if ($model->subjectTrainingRating) {
            return 'Grant theoretical exam access for ' . $model->subjectTrainingRating->name;
        } else {
            return 'Grant theoretical exam access for ' . $model->subjectTraining->getInlineRatings(true);
        }
    }

    public function getLink(Task $model)
    {
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
            $response = DivisionApi::assignTheoryExam($user, $rating, Auth::id());
            if ($response && $response->failed()) {
                return 'Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message'];
            }

            // Send email regarding exam access
            if (DivisionApi::getExamLink()) {
                $user->notify(new TrainingCustomNotification($training, 'Theoretical Exam', [
                    'You have been granted access to the theoretical exam for ' . $training->getInlineRatings(true) . '. You may now take the exam through the division website.',
                ], DivisionApi::getExamLink(), 'Take the Exam'));
            }

            TrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Theoretical exam access granted.');
        }

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
