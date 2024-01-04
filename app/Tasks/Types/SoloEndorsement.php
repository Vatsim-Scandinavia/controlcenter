<?php

namespace App\Tasks\Types;

use App\Models\Task;

class SoloEndorsement extends Types
{
    public function getName()
    {
        return 'Solo Endorsement';
    }

    public function getIcon()
    {
        return 'fa-clock';
    }

    public function getText(Task $model)
    {
        return 'Grant solo endorsement';
    }

    public function getLink(Task $model)
    {
        return route('endorsements.create.id', $model->subject_user_id);
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

    public function requireCheckboxConfirmation()
    {
        return 'The student has passed the required theoretical exam.';
    }

    public function showConnectedRatings()
    {
        return false;
    }

    public function allowNonVatsimRatings()
    {
        return false;
    }
}
