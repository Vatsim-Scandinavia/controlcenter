<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endorsement extends Model
{
    use HasFactory;

    public function ratings(){
        return $this->belongsToMany(Rating::class);
    }

    public function positions(){
        return $this->belongsToMany(Position::class);
    }

    public function areas(){
        return $this->belongsToMany(Area::class);
    }

    public function users(){
        return $this->belongsTo(User::class);
    }

}
