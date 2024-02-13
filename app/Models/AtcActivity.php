<?php

namespace App\Models;

use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Database\Eloquent\Model;

class AtcActivity extends Model
{
    protected $fillable = ['user_id', 'area_id', 'hours', 'start_of_grace_period'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'atc_active' => 'boolean',
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
