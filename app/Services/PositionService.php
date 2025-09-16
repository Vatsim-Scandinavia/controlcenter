<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PositionService
{
    /**
     *
     * @return Collection<Position> Positions.
     */
    public function getPositions(): Collection
    {
        $positions = Position::with('area')->get();
        $user = Auth::user();

        if (!$user) {
            return $positions->map(function ($position) {
                $position->can_edit = false;
                return $position;
            });
        }

        return $positions->map(function ($position) use ($user) {
            $position->editable = $user->isModeratorOrAbove($position->area);
            return $position;
        });
    }
}
