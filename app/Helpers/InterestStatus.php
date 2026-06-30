<?php

namespace App\Helpers;

/**
 * Enum representing the status of a training interest.
 */
enum InterestStatus: int
{
    case NOT_EXPIRED = 0;
    case CLOSED = 1;
    case EXPIRED = 2;
}
