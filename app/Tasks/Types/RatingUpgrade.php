<?php

namespace App\Tasks\Types;

use App\Models\Task;
use App\Models\User;
use App\Models\Training;
use App\Tasks\Types\Types;

class RatingUpgrade extends Types
{
    public function getName() {
        return 'Rating Upgrade';
    }

    public function getIcon() {
        return 'fa-circle-arrow-up';
    }

    public function getText(Task $model) {
        return 'Upgrade rating to ' . Training::find($model->reference_training_id)->getInlineRatings();
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