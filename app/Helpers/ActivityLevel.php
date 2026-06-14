<?php

namespace App\Helpers;

/**
 * Severity of an activity log entry. Values are lowercase so they double as the
 * CSS modifier used by the admin view (e.g. text-warning).
 */
enum ActivityLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';
}
