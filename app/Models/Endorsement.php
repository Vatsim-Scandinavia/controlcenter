<?php

namespace App\Models;

use App\Events\V1\EndorsementGranted;
use App\Events\V1\EndorsementRevoked;
use App\Helpers\LogName;
use App\Support\DomainEvents;
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

    /**
     * Fire versioned domain events at the authoritative Eloquent mutation
     * points: grant on creation, revoke when the `revoked` flag is raised.
     */
    protected static function booted(): void
    {
        static::updated(function (self $endorsement): void {
            if ($endorsement->wasChanged('revoked') && $endorsement->revoked) {
                DomainEvents::dispatch(new EndorsementRevoked($endorsement));
            }
        });
    }

    /**
     * Announce that this endorsement was granted. Called by the creation flow
     * once the endorsement's position/rating relations are attached, so
     * listeners see the complete record — a created() observer would fire with
     * none attached.
     */
    public function recordGranted(): void
    {
        DomainEvents::dispatch(new EndorsementGranted($this));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(LogName::Endorsement)
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
