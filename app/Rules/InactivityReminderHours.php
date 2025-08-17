<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use anlutro\LaravelSettings\Facade as Setting;

class InactivityReminderHours implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // The value must be minimum atcActivityRequirement + 1 or 0
        $minValue = Setting::get('atcActivityRequirement') + 1;

        if ($value != false && $value < $minValue) {
            $fail('The ATC inactivity reminder hours must be at least ' . $minValue . ' hours or 0 to disable.');
        }
    }
}
