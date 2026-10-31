<?php

namespace App\Models;

use App\Events\V1\TaskCreated;
use App\Events\V1\TaskStatusChanged;
use App\Helpers\TaskStatus;
use App\Support\DomainEvents;
use App\Tasks\TaskTypeRegistry;
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

    /**
     * Fire versioned domain events at the authoritative Eloquent mutation
     * points: creation, and any status transition (e.g. complete or decline).
     */
    protected static function booted(): void
    {
        static::created(function (self $task): void {
            DomainEvents::dispatch(new TaskCreated($task));
        });

        static::updated(function (self $task): void {
            if ($task->wasChanged('status')) {
                DomainEvents::dispatch(new TaskStatusChanged(
                    $task,
                    $task->getOriginal('status'),
                    $task->status,
                ));
            }
        });
    }

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
        if (! $this->type) {
            throw new \Exception('Invalid task type: ' . $this->type);
        }

        return app(TaskTypeRegistry::class)->make($this->type);
    }
}
