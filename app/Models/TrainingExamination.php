<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingExamination extends TrainingObject
{

    use HasFactory;

    protected $guarded = [];

    protected $dates = ['examination_date'];

    protected $casts = [
        'draft' => 'boolean',
    ];

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

}
