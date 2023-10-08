<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endorsement extends Model
{
    use HasFactory;

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
