<?php

namespace App\Models;

use App\Events\V1\BookingCreated;
use App\Events\V1\BookingDeleted;
use App\Support\DomainEvents;
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
     * Fire versioned domain events at the authoritative Eloquent mutation
     * points. Creation covers both the web and API paths. Deletion covers the
     * hard delete and the soft-delete flag; the `update:bookings` bulk cleanup
     * uses a query-builder delete that bypasses Eloquent and is not covered.
     */
    protected static function booted(): void
    {
        static::created(function (self $booking): void {
            // Reload so columns filled by database defaults are present on the
            // model the event carries, rather than null in memory.
            $booking->refresh();

            DomainEvents::dispatch(new BookingCreated($booking));
        });

        // A booking is cancelled by setting its `deleted` flag (see
        // BookingController); that is the single authoritative "deleted" moment.
        // The Eloquent delete() of the row is cleanup (and bulk cleanup bypasses
        // model events anyway), so it must NOT also dispatch — doing both
        // double-fired BookingDeleted across a booking's lifecycle.
        static::updated(function (self $booking): void {
            if ($booking->wasChanged('deleted') && $booking->deleted) {
                DomainEvents::dispatch(new BookingDeleted($booking));
            }
        });
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
