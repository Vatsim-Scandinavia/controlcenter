<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingReport extends TrainingObject
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean',
        'report_date' => 'datetime',
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
