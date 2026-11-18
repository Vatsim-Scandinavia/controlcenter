<?php

namespace App\Http\Requests;

use App\Models\Booking;

class UpdateBookingRequest extends BookingRequest
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
            ...$this->bookingRules(),
            'external_url' => 'nullable|url:http,https|max:2048',
            'external_id' => 'sometimes|string|max:128',
            /**
             * Required for EXTERNAL bookings. Increase this revision for every assignment change.
             */
            'external_revision' => $this->revisionRulesFor($booking),
            'source' => 'sometimes|string|in:CC,VATBOOK,DISCORD,EXTERNAL',
        ];
    }
}
