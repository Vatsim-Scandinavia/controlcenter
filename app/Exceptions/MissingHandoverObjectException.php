<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MissingHandoverObjectException extends Exception
{
    /**
     * MissingHandoverObjectException constructor.
     *
     * @param  string  $user
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($user = '', $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('Unable to acquire an instance of Handover class for user ' . $user, $code, $previous);
    }
}
