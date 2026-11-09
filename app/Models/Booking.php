<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['callsign', 'position_id', 'name', 'time_start', 'time_end', 'cid', 'user_id', 'training', 'event', 'exam'];

    /**
     * The booking's start time as a Carbon instance, for presentation.
     *
     * Not a cast on time_start: the booking flows mutate the Carbon instances they assign,
     * and the API serializes the stored format.
     *
     * @return Attribute<Carbon, never>
     */
    protected function startsAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => Carbon::parse($this->time_start));
    }

    /**
     * The booking's end time as a Carbon instance, for presentation.
     *
     * @return Attribute<Carbon, never>
     */
    protected function endsAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => Carbon::parse($this->time_end));
    }

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
