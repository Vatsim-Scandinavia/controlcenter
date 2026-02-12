<?php

namespace App\Http\Requests;

use App\Helpers\VatsimRating;
use App\Rules\VhfAirbandFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'fir' => 'FIR',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $position = $this->route('position');

        return [
            'callsign' => ['required', 'uppercase', Rule::unique('positions')->ignore($position)],
            'name' => 'required',
            'frequency' => ['required', new VhfAirbandFrequency],
            'fir' => 'required|size:4|uppercase',
            'rating' => [
                'required',
                'integer',
                Rule::in(VatsimRating::getPositionRatingValues()),
            ],
            'area_id' => 'required|integer|exists:areas,id',
        ];
    }
}
