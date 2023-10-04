<?php

namespace App\Helpers;

/**
 * Constants for task type.
 */
enum TaskType: int
{
    case THEORY_EXAM = 1;
    case SOLO_ENDORSEMENT = 2;
    case RATING_UPGRADE = 3;
    case MEMO = 4;
}
