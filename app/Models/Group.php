<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    public $timestamps = false;

    public function users(){
        return $this->belongsToMany(User::class, 'permissions')->withPivot('country_id')->withTimestamps();
    }
}
