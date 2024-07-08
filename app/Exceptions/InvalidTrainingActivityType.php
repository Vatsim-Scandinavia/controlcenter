<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidTrainingActivityType extends Exception
{
    /**
     * Missing InvalidTrainingActivityType constructor.
     *
     * @param  string  $user
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
