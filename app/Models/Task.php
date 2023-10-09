<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => TaskStatus::class,
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * The user which is the subject of the task.
     */
    public function subject()
    {
        return $this->belongsTo(User::class, 'reference_user_id');
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
