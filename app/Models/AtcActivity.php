<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AtcActivity extends Model
{
    protected $fillable = ['user_id', 'area_id', 'hours', 'start_of_grace_period', 'atc_active', 'last_online', 'hours_in_period'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'atc_active' => 'boolean',
        'last_online' => 'datetime',
        'start_of_grace_period' => 'datetime',
        'hours' => 'double',
        'hours_in_period' => 'double',
    ];

    public function scopeLocallyEligible(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $users) => $users
            ->where(fn (Builder $eligible) => $eligible
                ->where(config('app.mode'), config('app.owner_code'))
                ->orWhereHas('endorsements', fn (Builder $endorsements) => $endorsements
                    ->validVisiting()
                    ->whereHas('areas', fn (Builder $areas) => $areas->whereColumn('areas.id', 'atc_activities.area_id')))));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
