<?php

namespace App\Models;

use App\Helpers\VatsimRating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    public $timestamps = false;

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
