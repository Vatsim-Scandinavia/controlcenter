<?php

namespace App\Helpers;

/**
 * Constants for training status.
 */
enum TrainingStatus: int
{
    case CLOSED_BY_SYSTEM = -4;
    case CLOSED_BY_STUDENT = -3;
    case CLOSED_BY_STAFF = -2;
    case COMPLETED = -1;
    case IN_QUEUE = 0;
    case PRE_TRAINING = 1;
    case ACTIVE_TRAINING = 2;
    case AWAITING_EXAM = 3;
}
