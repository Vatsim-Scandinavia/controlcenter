<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingActivity extends Model
{
    use HasFactory;

    public $table = 'training_activity';

    public $fillable = [
        'triggered_by_id', 'type', 'old_data', 'new_data', 'comment',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }

    public function endorsement()
    {
        return $this->belongsTo(Endorsement::class, 'new_data');
    }
}
