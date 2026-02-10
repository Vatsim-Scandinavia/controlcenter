<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Position;
use Illuminate\Support\Collection;

class PositionService
{
    /**
     * Return all positions with the area sans other areas.
     *
     * @return Collection<Position> Positions.
     */
    public function getPositions(?Area $currentArea, Collection $accessibleAreas): Collection
    {
        return Position::with(['area', 'requiredRating'])
            ->when($currentArea, fn ($query) => $query->where('area_id', $currentArea->id))
            ->when(! $currentArea, fn ($query) => $query->whereIn('area_id', $accessibleAreas->pluck('id')))
            ->get();
    }
}
