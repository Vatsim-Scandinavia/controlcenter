<?php

namespace App\Contracts;

use App\Models\Booking;

interface VatsimBookingApiContract
{
    /**
     * Publish a new booking to the VATSIM booking API.
     *
     * @return int|null The VATSIM booking id, or null if nothing was published
     */
    public function createBooking(Booking $booking, string $type): ?int;

    /**
     * Update an existing booking on the VATSIM booking API.
     *
     * @return int|null The VATSIM booking id, or null if nothing was published
     */
    public function updateBooking(Booking $booking, string $type): ?int;

    /**
     * Remove a booking from the VATSIM booking API.
     */
    public function deleteBooking(Booking $booking): void;
}
