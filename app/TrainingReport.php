<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingReport extends Model
{

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean'
    ];

    public function path()
    {
        return route('training.report.edit', ['report' => $this->id]);
    }

    public function training(){
        return $this->belongsTo(Training::class);
    }

    public function user(){
        return $this->belongsTo(User::class, 'written_by_id');
    }

    public function attachments(){
        return $this->hasMany(TrainingReportAttachment::class);
    }
}
