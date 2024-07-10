<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TrainingObjectAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function object()
    {
        return $this->morphTo();
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
