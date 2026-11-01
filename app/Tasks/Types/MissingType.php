<?php

namespace App\Tasks\Types;

use App\Models\Task;

/**
 * Safe placeholder returned when a task's stored type key cannot be resolved,
 * for example after the module that provided it was disabled or uninstalled.
 * It renders inertly and performs no actions so existing tasks keep displaying.
 */
class MissingType extends Types
{
    public function __construct(private string $storedKey = 'unknown')
    {
        parent::__construct();
    }

    public function getName()
    {
        return 'Unavailable task type';
    }

    public function getIcon()
    {
        return 'fa-circle-question';
    }

    public function getText(Task $model)
    {
        return $model->message ?? 'This task type is no longer available.';
    }

    public function getLink(Task $model)
    {
        return false;
    }

    public function create(Task $model)
    {
        // The providing module is gone; do nothing.
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
