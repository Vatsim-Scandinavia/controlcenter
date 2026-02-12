<?php

namespace App\Rules;

use App\Services\VhfAirbandCheckerService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VhfAirbandFrequency implements ValidationRule
{
    protected VhfAirbandCheckerService $service;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->service = app(VhfAirbandCheckerService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->service->check((string) $value)) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    private function message(): string
    {
        return 'The frequency must be a valid VHF frequency between 118.000 and 136.990 MHz with valid spacing.';
    }
}
