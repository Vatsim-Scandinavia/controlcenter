<?php

namespace App\Events\V1;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking was deleted. Fired from {@see Booking} for both the Eloquent
 * `deleted` event (hard delete) and the soft-delete flag (`deleted` attribute
 * set to true). The bulk cleanup in the `update:bookings` command removes stale
 * rows with a query-builder delete that bypasses Eloquent and does not fire.
 */
class BookingDeleted
{
    use Dispatchable;

    public function __construct(public readonly Booking $booking) {}
}
