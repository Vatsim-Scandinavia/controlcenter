<?php

namespace App\Tasks;

use App\Models\Task;

abstract class TaskTypes
{
    protected $name;
    protected $icon;

    public function __construct()
    {
        $this->name = $this->getName();
        $this->icon = $this->getIcon();
    }

    public function onCreated(){
        // Default logic for creating a task
    }

    public function onCompleted(){
        // Default logic for completing a task
    }

    public function onDeclined(){
        // Default logic for declining a task
    }

    abstract public function getName();
    abstract public function getIcon();
    abstract public function getText(Task $model);
    abstract public function getLink(Task $model);

}