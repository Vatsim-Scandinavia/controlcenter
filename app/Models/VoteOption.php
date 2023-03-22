<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteOption extends Model
{
    public $timestamps = false;

    public function vote()
    {
        return $this->belongsTo(Vote::class);
    }
}
