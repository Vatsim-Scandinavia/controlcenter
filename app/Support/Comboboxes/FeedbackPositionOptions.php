<?php

namespace App\Support\Comboboxes;

use App\Contracts\ComboboxOptionProvider;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Combobox options for the feedback position filter: positions within the
 * current user's permitted areas, optionally narrowed to a selected area,
 * matched by callsign or name.
 */
class FeedbackPositionOptions implements ComboboxOptionProvider
{
    public const LIMIT = 15;

    public function options(string $search, array $context = []): Collection
    {
        $scope = auth()->user()->accessibleAreasForPermission('feedback.correlated.view');
        $area = $context['area'] ?? null;

        return Position::query()
            ->when(! $scope->isGlobal, fn (Builder $q) => $q->whereIn('area_id', $scope->areas->pluck('id')))
            ->when($area !== null, fn (Builder $q) => $q->where('area_id', $area))
            ->where(fn (Builder $q) => $q
                ->where('callsign', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%'))
            ->orderBy('callsign')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Position $position): array => [
                'value' => $position->callsign,
                'label' => "{$position->callsign} - {$position->name}",
            ]);
    }
}
