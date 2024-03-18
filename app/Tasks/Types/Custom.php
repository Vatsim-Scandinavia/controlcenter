<?php

namespace App\Tasks\Types;

use App\Models\Task;

class Custom extends Types
{
    public function getName()
    {
        return 'Custom Request';
    }

    public function getIcon()
    {
        return 'fa-message';
    }

    public function getText(Task $model)
    {
        return $model->message;
    }

    public function getLink(Task $model)
    {
        return false;
    }

    public function allowMessage()
    {
        return true;
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
