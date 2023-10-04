<?php

namespace App\Tasks\Types;

use App\Tasks\TaskTypes;
use App\Models\Task;

class CustomRequest extends Types
{
    public function getName() {
        return 'Custom Request';
    }

    public function getIcon() {
        return 'fa-message';
    }

    public function getText(Task $model) {
        return $model->message;
    }

    public function getLink(Task $model){
        return false;
    }

}