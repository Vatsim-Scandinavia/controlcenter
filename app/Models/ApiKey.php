<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $table = 'api_keys';

    public $timestamps = false;

    public $fillable = [
        'id', 'name', 'last_used_at', 'read_only', 'created_at',
    ];
}
