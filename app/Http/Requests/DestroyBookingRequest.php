<?php

namespace App\Http\Requests;

use App\Models\Booking;

class DestroyBookingRequest extends BookingRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Booking|null $booking */
        $booking = $this->route('booking');

        return [
            /**
             * Required for EXTERNAL bookings. Must be newer than the last accepted publication or update.
             */
            'external_revision' => $this->revisionRulesFor($booking),
        ];
    }
}
