<?php

namespace App\Tasks\Types;

use App\Models\Task;

/**
 * Abstract class for task types
 * NOTE: Experimental feature, this interface/class is not stable. Changes to architecture may occur.
 */
abstract class Types
{
    protected $name;

    protected $icon;

    public function __construct()
    {
        $this->name = $this->getName();
        $this->icon = $this->getIcon();
    }

    public function onCreated(Task $model)
    {
        // Default behaviour when task is created
    }

    public function onCompleted(Task $model)
    {
        // Default behaviour is completed
    }

    public function onDeclined(Task $model)
    {
        // Default behaviour is declined
    }

    public function allowMessage()
    {
        return false;
    }

    public function requireCheckboxConfirmation()
    {
        return false;
    }

    public function requireRatingSelection()
    {
        return false;
    }

    public function isApproval()
    {
        return false;
    }

    abstract public function getName();

    abstract public function getIcon();

    abstract public function getText(Task $model);

    abstract public function getLink(Task $model);

    abstract public function create(Task $model);

    abstract public function complete(Task $model);

    abstract public function decline(Task $model);

    abstract public function showConnectedRatings();

    abstract public function allowNonVatsimRatings();
}
