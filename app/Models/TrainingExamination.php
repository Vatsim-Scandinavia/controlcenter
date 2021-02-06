<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingExamination extends TrainingObject
{

    protected $guarded = [];

    protected $dates = ['examination_date'];

    protected $casts = [
        'draft' => 'boolean',
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
