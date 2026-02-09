<?php

namespace App\Exceptions;

use RuntimeException;

class StatisticsApiException extends RuntimeException
{
    protected int $httpStatus;

    public function __construct(string $message = 'Statistics API error', int $httpStatus = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
