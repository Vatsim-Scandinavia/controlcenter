<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use anlutro\LaravelSettings\Facade as Setting;

class AtcActivity extends Model
{
    protected $fillable = ['user_id', 'area_id', 'hours', 'start_of_grace_period'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function isActive(){
        return $this->hours >= Setting::get('atcActivityRequirement') || ($this->start_of_grace_period != null && $this->start_of_grace_period->addMonths(Setting::get('atcActivityGracePeriod'))->isFuture());
    }
}
