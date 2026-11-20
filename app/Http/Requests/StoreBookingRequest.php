<?php

namespace App\Http\Requests;

class StoreBookingRequest extends BookingRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->bookingRules(),
            /**
             * The booking source: CC, VATBOOK, DISCORD, or EXTERNAL.
             *
             * @example CC
             */
            'source' => 'required|string|in:CC,VATBOOK,DISCORD,EXTERNAL',
            /**
             * Stable assignment identifier, required for EXTERNAL bookings. Reuse it when republishing.
             */
            'external_id' => 'required_if:source,EXTERNAL|prohibited_unless:source,EXTERNAL|string|max:128',
            /**
             * Monotonically increasing assignment revision (1–2147483647), required for EXTERNAL bookings.
             */
            'external_revision' => 'required_if:source,EXTERNAL|prohibited_unless:source,EXTERNAL|integer|min:1|max:2147483647',
            /**
             * Optional HTTP or HTTPS link to manage an EXTERNAL booking in the originating system.
             */
            'external_url' => 'nullable|prohibited_unless:source,EXTERNAL|url:http,https|max:2048',
        ];
    }
}
