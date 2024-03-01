<?php

namespace App\Models;

use App\Helpers\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => TaskStatus::class,
        'closed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function subjectTraining()
    {
        return $this->belongsTo(Training::class, 'subject_training_id');
    }

    public function subjectTrainingRating()
    {
        return $this->belongsTo(Rating::class, 'subject_training_rating_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function type()
    {
        if ($this->type) {
            return app($this->type);
        } else {
            throw new \Exception('Invalid task type: ' . $this->type);
        }
    }
}
