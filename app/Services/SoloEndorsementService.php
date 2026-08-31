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
     * Whole days an endorsement grants. An endorsement with no end date grants
     * nothing countable, and a backwards range counts as zero rather than
     * crediting days back to the student.
     *
     * Rounded up, because a part day is a day the student was on solo: an
     * endorsement issued at 14:00 and expiring at 12:00 twenty days later spans
     * 19.9 days, and counting that as 19 would quietly hand back a day of the
     * allowance on every endorsement.
     */
    public function daysGranted(Endorsement $endorsement): int
    {
        if (! $endorsement->valid_from || ! $endorsement->valid_to) {
            return 0;
        }

        return (int) max(0, ceil($endorsement->valid_from->diffInDays($endorsement->valid_to)));
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
