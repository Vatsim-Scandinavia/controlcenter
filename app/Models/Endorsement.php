<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Endorsement extends Model
{
    use HasFactory, LogsActivity;

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('endorsement')
            ->logOnly(['type', 'valid_from', 'valid_to', 'expired', 'revoked', 'user_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
