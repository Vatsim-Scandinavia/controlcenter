<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Feedback extends Model
{
    use HasFactory;
    use Notifiable;

    protected $guarded = [];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function referenceUser()
    {
        return $this->belongsTo(User::class, 'reference_user_id');
    }

    public function referencePosition()
    {
        return $this->belongsTo(Position::class, 'reference_position_id');
    }
}
