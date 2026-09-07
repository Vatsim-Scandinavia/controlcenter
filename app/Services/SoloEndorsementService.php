<?php

namespace App\Services;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Endorsement;
use App\Models\Training;
use Illuminate\Support\Collection;

/**
 * Totals up the solo endorsement days granted on a training, so staff can see
 * how much of the allowance is left before issuing another one.
 */
class SoloEndorsementService
{
    /**
     * Fallback allowance for installations whose setting has not been seeded.
     */
    private const DEFAULT_MAX_DAYS = 60;

    /**
     * The solo endorsements recorded against a training and how much of the day
     * allowance they consume, or null when the training has none.
     *
     * @return array{endorsements: Collection<int, array{endorsement: Endorsement, days: int}>, used: int, left: int, total: int, percentage_used: int, collapsed: bool}|null
     */
    public function summaryFor(Training $training): ?array
    {
        $endorsements = $this->endorsementsFor($training)
            ->map(fn (Endorsement $endorsement): array => [
                'endorsement' => $endorsement,
                'days' => $this->daysGranted($endorsement),
            ]);

        if ($endorsements->isEmpty()) {
            return null;
        }

        $total = $this->maxDays();
        // Every day granted counts, revoked ones included: the allowance is about
        // how much solo time has been issued, not how much was flown.
        $used = (int) $endorsements->sum('days');
        $percentageUsed = (int) min(100, round(($used / $total) * 100));

        return [
            'endorsements' => $endorsements,
            'used' => $used,
            'left' => max($total - $used, 0),
            'total' => $total,
            'percentage_used' => $percentageUsed,
            // Nothing left to act on once the allowance is spent or the training
            // is over, so the breakdown starts folded away.
            'collapsed' => $used >= $total || $training->status->isClosed(),
        ];
    }

    /**
     * Solo endorsements recorded against this training through its activity log.
     *
     * Endorsements carry no training reference of their own, so the ENDORSEMENT
     * activity written when one is issued is the only link between the two.
     *
     * @return Collection<int, Endorsement>
     */
    public function endorsementsFor(Training $training): Collection
    {
        $endorsementIds = $training->activities
            ->where('type', 'ENDORSEMENT')
            ->pluck('new_data')
            ->filter()
            ->unique();

        if ($endorsementIds->isEmpty()) {
            return collect();
        }

        return Endorsement::where('type', 'SOLO')
            ->where('user_id', $training->user_id)
            ->whereIn('id', $endorsementIds)
            ->orderByDesc('valid_from')
            ->get();
    }

    /**
     * Whole days an endorsement grants, counted on calendar dates: one issued on
     * the 1st and expiring on the 31st grants 30 days. valid_from is the issue
     * time while valid_to is pinned to 12:00, so raw timestamps would count an
     * extra day for every solo issued before noon.
     *
     * No end date or a backwards range grants nothing. A same-day solo grants one.
     */
    public function daysGranted(Endorsement $endorsement): int
    {
        $from = $endorsement->valid_from;
        $to = $endorsement->valid_to;

        if (! $from || ! $to || $to->lessThanOrEqualTo($from)) {
            return 0;
        }

        return max(1, (int) round($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())));
    }

    /**
     * The configured day allowance, floored at one day so the percentage
     * calculation can never divide by zero.
     */
    public function maxDays(): int
    {
        return max(1, (int) Setting::get('trainingSoloMaxDays', self::DEFAULT_MAX_DAYS));
    }
}
