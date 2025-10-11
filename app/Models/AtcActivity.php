<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtcActivity extends Model
{
    protected $fillable = ['user_id', 'area_id', 'hours', 'start_of_grace_period', 'atc_active', 'last_online', 'last12Months'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'atc_active' => 'boolean',
        'last_online' => 'datetime',
        'start_of_grace_period' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}