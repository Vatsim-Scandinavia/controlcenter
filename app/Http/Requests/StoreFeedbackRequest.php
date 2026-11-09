<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Callsigns and airport codes are stored uppercase, however they were typed.
     */
    protected function prepareForValidation(): void
    {
        foreach (['pilot_callsign', 'departure_airport', 'arrival_airport'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper($this->input($field))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'position' => 'nullable|exists:positions,callsign',
            'controller' => 'nullable|numeric|exists:users,id',
            'pilot_callsign' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/'],
            'event_date' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'departure_airport' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4}$/'],
            'arrival_airport' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4}$/'],
            'feedback' => 'required|string|max:16000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'controller.numeric' => 'The controller field must be a valid VATSIM CID (numeric).',
            'controller.exists' => 'A controller with this CID was not found.',
            'position.exists' => 'The position does not exist.',
            'pilot_callsign.regex' => 'The callsign may only contain letters, digits, dashes and underscores.',
            'event_date.before_or_equal' => 'The date of the event cannot be in the future.',
            'departure_airport.regex' => 'The departure airport must be a 4 character ICAO code.',
            'arrival_airport.regex' => 'The arrival airport must be a 4 character ICAO code.',
        ];
    }
}
