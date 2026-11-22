<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $table = 'api_keys';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    public $fillable = [
        'id', 'name', 'last_used_at', 'read_only', 'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_only' => 'boolean',
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
