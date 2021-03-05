<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingObject extends Model
{
    public function attachments()
    {
        return $this->morphMany(TrainingObjectAttachment::class, 'object');
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
