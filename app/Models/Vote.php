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
        static::updated(function (self $vote): void {
            if ($vote->wasChanged('closed') && $vote->closed) {
                DomainEvents::dispatch(new VoteClosed($vote));
            }
        });
    }

    /**
     * Announce that this vote was created. Called by the creation flow once the
     * vote's options are attached, so listeners see the complete vote — a
     * created() observer would fire with no options.
     */
    public function recordCreated(): void
    {
        DomainEvents::dispatch(new VoteCreated($this));
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
