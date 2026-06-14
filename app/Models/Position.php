<?php

namespace App\Models;

use App\Helpers\VatsimRating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Position extends Model
{
    use HasFactory, LogsActivity;

    public $timestamps = false;

    /**
     * Record creates, updates and deletes to the activity log under the "sector"
     * category, with a human-readable description identifying the position.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sector')
            ->logOnly(['callsign', 'name', 'frequency', 'fir', 'rating', 'area_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Position {$eventName}: {$this->callsign}");
    }

    protected $fillable = [
        'callsign',
        'name',
        'frequency',
        'fir',
        'rating',
        'area_id',
    ];

    protected $casts = [
        'rating' => VatsimRating::class,
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'id', 'position_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function endorsements()
    {
        return $this->belongsToMany(Endorsement::class);
    }

    public function requiredRating()
    {
        return $this->belongsTo(Rating::class, 'required_facility_rating_id');
    }

    /**
     * Get the name of the VATSIM rating for this position
     */
    public function getRatingNameAttribute()
    {
        return $this->rating->name;
    }

    /**
     * Check if the position has the given base rating
     */
    public function hasBaseRating(VatsimRating $rating): bool
    {
        return $this->rating === $rating;
    }
}
