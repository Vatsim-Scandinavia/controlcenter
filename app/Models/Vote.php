<?php

namespace App\Models;

use App\Events\V1\VoteClosed;
use App\Events\V1\VoteCreated;
use App\Support\DomainEvents;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $casts = [
        'closed' => 'boolean',
    ];

    /**
     * Fire versioned domain events at the authoritative Eloquent mutation
     * points: creation, and closing when the `closed` flag is raised. The
     * scheduled `clean:votes` expiry iterates models so this fires there too.
     */
    protected static function booted(): void
    {
        static::created(function (self $vote): void {
            DomainEvents::dispatch(new VoteCreated($vote));
        });

        static::updated(function (self $vote): void {
            if ($vote->wasChanged('closed') && $vote->closed) {
                DomainEvents::dispatch(new VoteClosed($vote));
            }
        });
    }

    public function option()
    {
        return $this->hasMany(VoteOption::class);
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
