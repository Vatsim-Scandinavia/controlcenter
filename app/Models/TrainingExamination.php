<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingExamination extends TrainingObject
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean',
        'examination_date' => 'datetime',
    ];

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function examiner()
    {
        return $this->hasOne(User::class, 'id', 'examiner_id');
    }
}
