<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    public $timestamps = false;

    public function trainings()
    {
        return $this->belongsToMany(Training::class);
    }

    public function endorsements()
    {
        return $this->belongsToMany(Endorsement::class);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class)->withPivot('required_vatsim_rating', 'allow_bundling', 'hour_requirement', 'queue_length_low', 'queue_length_high');
    }

    public function requiredByPositions()
    {
        return $this->hasMany(Position::class, 'required_facility_rating_id');
    }
}
