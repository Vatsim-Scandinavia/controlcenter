<?php

namespace App\Tasks\Types;

use App\Tasks\Types\Types;
use App\Models\Task;
use App\Models\User;
use App\Models\Training;

class TheoreticalExam extends Types
{
    public function getName() {
        return 'Theoretical Exam Access';
    }

    public function getIcon() {
        return 'fa-key';
    }

    public function getText(Task $model) {
        return 'Grant theoretical exam access for ' . Training::find($model->reference_training_id)->getInlineRatings(true);
    }

    public function getLink(Task $model){
        $user = User::find($model->reference_user_id);
        $userEud = $user->division == 'EUD';

        if($userEud){
            return 'https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid=' . $model->reference_user_id;
        }

        return false;
    }

    public function create(Task $model){
        parent::onCreated($model);
    }

    public function complete(Task $model){
        parent::onCompleted($model);
    }

    public function decline(Task $model){
        parent::onDeclined($model);
    }

    public function showConnectedRatings(){
        return true;
    }
}