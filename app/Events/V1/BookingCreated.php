<?php

namespace App\Events\V1;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking was created. Fired from the authoritative Eloquent `created` event
 * on {@see Booking}, covering both the web and API creation paths.
 */
class BookingCreated
{
    use Dispatchable;

    public function __construct(public readonly Booking $booking) {}
}
