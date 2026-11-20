<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating, updating and cancelling bookings through the API.
 */
abstract class BookingRequest extends FormRequest
{
    /**
     * Access is enforced by the API token middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the booking source so validation and ownership checks are case-insensitive.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('source'))) {
            $this->merge(['source' => strtoupper($this->input('source'))]);
        }
    }

    /**
     * The rules shared by booking creation and updates.
     *
     * @return array<string, mixed>
     */
    protected function bookingRules(): array
    {
        return [
            /**
             * The VATSIM CID (user ID) the booking belongs to.
             *
             * @example 10000001
             */
            'cid' => 'required|integer',
            /**
             * The booking date, in `d/m/Y` format.
             *
             * @example 15/02/2024
             */
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            /**
             * The booking start time, in 24-hour `H:i` format.
             *
             * @example 12:00
             */
            'start_at' => 'required|date_format:H:i',
            /**
             * The booking end time, in 24-hour `H:i` format.
             *
             * @example 13:00
             */
            'end_at' => 'required|date_format:H:i',
            /**
             * The callsign of the position to book.
             *
             * @example EKCH_TWR
             */
            'position' => 'required|exists:positions,callsign',
            /**
             * The booking type: `1` for training, `2` for exam, `3` for event.
             *
             * @example 1
             */
            'tag' => 'nullable|integer|between:1,3',
        ];
    }

    /**
     * Revision rules for mutating an existing booking: required for EXTERNAL bookings, prohibited otherwise.
     *
     * @return array<int, mixed>
     */
    protected function revisionRulesFor(?Booking $booking): array
    {
        $isExternal = $booking?->source === 'EXTERNAL';

        return [Rule::requiredIf($isExternal), Rule::prohibitedIf(! $isExternal), 'integer', 'min:1', 'max:2147483647'];
    }
}
