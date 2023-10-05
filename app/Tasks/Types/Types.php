<?php

namespace App\Tasks\Types;

use App\Models\Task;
use App\Helpers\TaskStatus;

abstract class Types
{
    protected $name;
    protected $icon;

    public function __construct()
    {
        $this->name = $this->getName();
        $this->icon = $this->getIcon();
    }

    public function onCreated(Task $model){
        
    }

    public function onCompleted(Task $model){
        $model->update([
            'status' => TaskStatus::COMPLETED->value
        ]);
    }

    public function onDeclined(Task $model){
        $model->update([
            'status' => TaskStatus::DECLINED->value
        ]);
    }

    public function allowMessage(){
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

}