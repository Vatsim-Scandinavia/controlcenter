<?php

namespace App\Tasks\Types;

use App\Http\Controllers\TrainingActivityController;
use App\Models\Task;

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
        return 'Grant theoretical exam access for ' . $model->subjectTraining->getInlineRatings(true);
    }

    public function getLink(Task $model)
    {
        $user = $model->subject;
        $userEud = $user->division == 'EUD';

        if ($userEud) {
            return 'https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid=' . $model->subject_user_id;
        }

        return false;
    }

    public function create(Task $model)
    {
        parent::onCreated($model);
    }

    public function complete(Task $model)
    {
        TrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Theoretical exam access granted.');
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
