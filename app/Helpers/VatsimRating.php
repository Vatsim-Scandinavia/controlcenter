<?php

namespace App\Helpers;

/**
 * Constants for VATSIM ratings.
 */
enum VatsimRating: int
{
    case INA = -1;
    case SUS = 0;
    case OBS = 1;
    case S1 = 2;
    case S2 = 3;
    case S3 = 4;
    case C1 = 5;
    case C3 = 7;
    case I1 = 8;
    case I3 = 10;
    case SUP = 11;
    case ADM = 12;
}
