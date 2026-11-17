<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $areaId = $this->filled('area') ? $this->integer('area') : null;

        return [
            'area' => 'nullable|integer|exists:areas,id',
            'position' => [
                'nullable',
                Rule::exists('positions', 'callsign')->when(
                    $areaId !== null,
                    fn ($rule) => $rule->where('area_id', $areaId)
                ),
            ],
            'controller' => 'nullable|numeric|exists:users,id',
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
            'position.exists' => 'The position does not exist in the selected area.',
            'area.exists' => 'The selected area does not exist.',
        ];
    }
}
