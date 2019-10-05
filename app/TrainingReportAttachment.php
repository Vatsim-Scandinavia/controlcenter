<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingReportAttachment extends Model
{
    public function report(){
        return $this->belongTo(TrainingReport::class);
    }
}
