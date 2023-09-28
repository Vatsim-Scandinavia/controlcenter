<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtcActivity extends Model
{
    public $primaryKey = 'user_id';

    protected $fillable = ['user_id', 'hours', 'start_of_grace_period'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_of_grace_period' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
