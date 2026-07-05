<?php

namespace App\Helpers;

use App\Traits\ComparableIntEnum;

/**
 * Enum representing the status of a training interest.
 */
enum InterestStatus: int
{
    use ComparableIntEnum;
    case NOT_EXPIRED = 0;
    case CLOSED = 1;
    case EXPIRED = 2;
}
