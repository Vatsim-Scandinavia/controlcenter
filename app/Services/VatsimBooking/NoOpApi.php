<?php

namespace App\Services\VatsimBooking;

use App\Contracts\VatsimBookingApiContract;
use App\Models\Booking;

/**
 * No-op implementation used outside production, where bookings
 * are not published to the central VATSIM booking API.
 */
class NoOpApi implements VatsimBookingApiContract
{
    public function createBooking(Booking $booking, string $type): ?int
    {
        return null;
    }

    public function updateBooking(Booking $booking, string $type): ?int
    {
        return null;
    }

    public function deleteBooking(Booking $booking): void
    {
        // Intentionally left blank
    }
}
