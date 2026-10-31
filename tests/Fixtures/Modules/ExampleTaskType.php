<?php

namespace Tests\Fixtures\Modules;

use App\Models\Task;
use App\Tasks\Types\Types;

class ExampleTaskType extends Types
{
    public function getName()
    {
        return 'Example Review';
    }

    public function getIcon()
    {
        return 'fa-flask';
    }

    public function getText(Task $model)
    {
        return $model->message;
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
        parent::onCompleted($model);
    }

    public function decline(Task $model)
    {
        parent::onDeclined($model);
    }

    public function showConnectedRatings()
    {
        return false;
    }

    public function allowNonVatsimRatings()
    {
        return true;
    }
}
