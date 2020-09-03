<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingObject extends Model
{
    public function attachments(){
        return $this->morphMany(TrainingObjectAttachment::class, 'object');
    }
}
