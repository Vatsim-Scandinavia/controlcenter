<?php

namespace App\Tasks\Types;

use App\Tasks\TaskTypes;
use App\Models\Task;
use App\Models\User;
use App\Models\Training;

class TheoreticalExam extends TaskTypes
{
    public function getName() {
        return 'Theoretical Exam Access';
    }

    public function getIcon() {
        return 'fa-key';
    }

    public function getText(Task $model) {
        return 'Grant theoretical exam access';
    }

    public function getLink(Task $model){
        $user = User::find($model->reference_user_id);
        $userEud = $user->division == 'EUD';

        if($userEud){
            return 'https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid=' . $model->reference_user_id;
        }

        return false;
    }
}