<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Area extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function endorsements()
    {
        return $this->belongsToMany(Endorsement::class);
    }

    /**
     * @return BelongsToMany<Rating, $this>
     */
    public function ratings(): BelongsToMany
    {
        return $this->belongsToMany(Rating::class)->withPivot('required_vatsim_rating', 'allow_bundling', 'hour_requirement', 'queue_length_low', 'queue_length_high');
    }

    public function roleUsers()
    {
        return $this->belongsToMany(User::class, 'role_user')->withPivot('role')->withTimestamps();
    }

    public function mentors()
    {
        return $this->belongsToMany(User::class, 'role_user')->withPivot('role')->withTimestamps()->wherePivot('role', 'mentor');
    }

    public function positions()
    {
        return $this->hasMany(Position::class)->with('area');
    }
}
