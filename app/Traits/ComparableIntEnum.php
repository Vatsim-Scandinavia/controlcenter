<?php

namespace App\Traits;

trait ComparableIntEnum
{
    public function isGreaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->value >= $other->value;
    }

    public function isLessThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        return $this->value <= $other->value;
    }
}
