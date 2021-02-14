<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoloEndorsement extends Model
{

    use HasFactory;

    protected $dates = ['expires_at'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function training(){
        return $this->belongsTo(Country::class);
    }

}