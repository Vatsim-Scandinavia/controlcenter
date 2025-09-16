<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Collection;

class PositionService
{
    /**
     * Return all positions with the area.
     *
     * @return Collection<Position> Positions.
     */
    public function getPositions(): Collection
    {
        $positions = Position::with('area')->get();

        return $positions;
    }
}
