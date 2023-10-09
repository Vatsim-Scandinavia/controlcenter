<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\TaskStatus;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => TaskStatus::class,
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function asignee(){
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