<?php

namespace App\Models;

use Database\Factories\MoodleUserLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoodleUserLink extends Model
{
    /** @use HasFactory<MoodleUserLinkFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(MoodleEnrolment::class);
    }
}
