<?php

namespace App\Helpers;

use App\Traits\ComparableIntEnum;

/**
 * Constants for training status.
 */
enum TrainingStatus: int
{
    use ComparableIntEnum;
    case CLOSED_BY_SYSTEM = -4;
    case CLOSED_BY_STUDENT = -3;
    case CLOSED_BY_STAFF = -2;
    case COMPLETED = -1;
    case IN_QUEUE = 0;
    case PRE_TRAINING = 1;
    case ACTIVE_TRAINING = 2;
    case AWAITING_EXAM = 3;

    public function label(): string
    {
        return match ($this) {
            self::CLOSED_BY_SYSTEM => 'Closed by system',
            self::CLOSED_BY_STUDENT => 'Closed by student',
            self::CLOSED_BY_STAFF => 'Closed by staff',
            self::COMPLETED => 'Completed',
            self::IN_QUEUE => 'In queue',
            self::PRE_TRAINING => 'Pre-training',
            self::ACTIVE_TRAINING => 'Active training',
            self::AWAITING_EXAM => 'Awaiting exam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CLOSED_BY_SYSTEM,
            self::CLOSED_BY_STUDENT,
            self::CLOSED_BY_STAFF => 'danger',
            self::COMPLETED,
            self::ACTIVE_TRAINING => 'success',
            self::IN_QUEUE,
            self::AWAITING_EXAM => 'warning',
            self::PRE_TRAINING => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CLOSED_BY_SYSTEM,
            self::CLOSED_BY_STUDENT => 'fa fa-ban',
            self::CLOSED_BY_STAFF => 'fas fa-ban',
            self::COMPLETED => 'fas fa-check',
            self::IN_QUEUE => 'fas fa-hourglass',
            self::PRE_TRAINING,
            self::ACTIVE_TRAINING => 'fas fa-book-open',
            self::AWAITING_EXAM => 'fas fa-graduation-cap',
        };
    }

    public function isAssignableByStaff(): bool
    {
        return match ($this) {
            self::CLOSED_BY_SYSTEM,
            self::CLOSED_BY_STUDENT => false,
            default => true,
        };
    }

    public function isOpen(): bool
    {
        return $this->isGreaterThanOrEqual(self::IN_QUEUE);
    }

    public function isClosed(): bool
    {
        return $this->isLessThan(self::IN_QUEUE);
    }

    public function isInProgress(): bool
    {
        return $this->isGreaterThanOrEqual(self::PRE_TRAINING);
    }
}
