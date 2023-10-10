<?php

namespace App\Rules;

use App\Http\Controllers\TaskController;
use Illuminate\Contracts\Validation\Rule;

class ValidTaskType implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return TaskController::isValidType($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected task type does not exist.';
    }
}
