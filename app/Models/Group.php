<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    public $timestamps = false;

    public function users(){
        return $this->hasMany(User::class);
    }

    public function permissions(){
        return $this->hasMany(Permission::class);
    }
}
