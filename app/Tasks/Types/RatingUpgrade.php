<?php

namespace App\Tasks\Types;

use App\Http\Controllers\TrainingActivityController;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;

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

        if ($userEud && $training->hasVatsimRatings()) {
            return 'https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid=' . $model->subject_user_id;
        } elseif ($userEud && ! $training->hasVatsimRatings()) {
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
        TrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Rating upgrade requested.');
        parent::onCompleted($model);
    }

    public function decline(Task $model)
    {
        parent::onDeclined($model);
    }

    public function requireCheckboxConfirmation()
    {
        return 'The student has passed the required theoretical exam.';
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
