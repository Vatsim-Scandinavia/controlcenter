<?php

namespace App\Helpers;

/**
 * Constants for task status.
 */
enum TaskStatus: int
{
    case PENDING = 0;
    case DECLINED = -1;
    case COMPLETED = 1;
}
