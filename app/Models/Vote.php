<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    public function option()
    {
        return $this->hasMany(VoteOption::class);
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
