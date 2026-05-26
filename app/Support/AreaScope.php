<?php

namespace App\Support;

use Illuminate\Support\Collection;

class AreaScope
{
    private function __construct(
        public readonly bool $isGlobal,
        public readonly Collection $areas,
    ) {}

    public static function global(): self
    {
        return new self(true, collect());
    }

    public static function forAreas(Collection $areas): self
    {
        return new self(false, $areas);
    }

    public function hasAccess(): bool
    {
        return $this->isGlobal || $this->areas->isNotEmpty();
    }
}
