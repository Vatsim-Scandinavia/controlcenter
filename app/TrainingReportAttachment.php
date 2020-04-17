<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingReportAttachment extends Model
{

    protected $guarded = [];

    protected $casts = [
        'hidden' => 'boolean'
    ];

    public function report(){
        return $this->belongsTo(TrainingReport::class, 'training_report_id');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

}
