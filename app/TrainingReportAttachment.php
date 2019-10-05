<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingReportAttachment extends Model
{
    public function training_report(){
        return $this->belongTo(TrainingReport::class);
    }
}
