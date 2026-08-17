<?php

namespace App\Helpers;

/**
 * Category of an activity log entry. Values are lowercase because they are
 * stored verbatim as the activity log_name.
 */
enum LogName: string
{
    case Access = 'access';
    case Booking = 'booking';
    case Endorsement = 'endorsement';
    case Feedback = 'feedback';
    case Other = 'other';
    case Role = 'role';
    case Sector = 'sector';
    case Training = 'training';
}
