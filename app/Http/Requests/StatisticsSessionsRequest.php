<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatisticsSessionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Note that all users are allowed to access the endpoint because
     * the data is publicly accessible via the upstream statistics API.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'from.required' => 'Start date is required',
            'from.date' => 'Start date must be a valid date',
            'to.required' => 'End date is required',
            'to.date' => 'End date must be a valid date',
            'to.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }
}
