<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingReport extends TrainingObject
{
    use HasFactory;

    protected $guarded = [];

    protected $dates = ['report_date'];

    protected $casts = [
        'draft' => 'boolean',
    ];

    public function path()
    {
        return route('training.report.edit', ['report' => $this->id]);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'written_by_id');
    }
}
