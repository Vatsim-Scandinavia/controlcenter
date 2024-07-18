<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    public $timestamps = false;

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
}
