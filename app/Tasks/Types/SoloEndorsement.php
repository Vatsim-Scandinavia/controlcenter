<?php

namespace App\Tasks\Types;

use App\Tasks\Types\Types;
use App\Models\Task;
use App\Models\User;
use App\Models\Training;

class SoloEndorsement extends Types
{
    public function getName() {
        return 'Solo Endorsement';
    }

    public function getIcon() {
        return 'fa-clock';
    }

    public function getText(Task $model) {
        return 'Grant solo endorsement';
    }

    public function getLink(Task $model){
        return route('endorsements.create.id', $model->reference_user_id);
    }
}