<?php

namespace App\Facades;

use App\Contracts\VatsimBookingApiContract;
use App\Services\VatsimBooking\Api;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int|null createBooking(\App\Models\Booking $booking, string $type)
 * @method static int|null updateBooking(\App\Models\Booking $booking, string $type)
 * @method static void deleteBooking(\App\Models\Booking $booking)
 *
 * @see Api
 */
class VatsimBookingApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return VatsimBookingApiContract::class;
    }
}
