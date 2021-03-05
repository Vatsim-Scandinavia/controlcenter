<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingObjectAttachment extends Model
{

    protected $guarded = [];

    protected $casts = [
        'hidden' => 'boolean'
    ];

    public function object()
    {
        return $this->morphTo();
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

}
