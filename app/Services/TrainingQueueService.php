<?php

namespace App\Services;

use App\Helpers\TrainingStatus;
use App\Models\Rating;
use App\Models\Training;
use Illuminate\Support\Collection;

/**
 * Derives the training queues from open requests.
 *
 * A queue is not stored anywhere: it is every request In Queue that shares an area and
 * a level, ordered by application date. Paused requests keep their place. The level is
 * the highest VATSIM rating of the request, or its full set of endorsement ratings if it
 * holds no VATSIM rating, so every request belongs to exactly one queue.
 */
class TrainingQueueService
{
    /**
     * Get the place of a training in its queue.
     *
     * @return array{position: int, total: int, name: string}|null null unless the training is in queue
     */
    public function positionOf(Training $training): ?array
    {
        if ($training->status !== TrainingStatus::IN_QUEUE) {
            return null;
        }

        $queueKey = $this->queueKey($training->area_id, $training->ratings);

        $queue = $this->queuedTrainings($training->area_id)
            ->filter(fn (Training $queued) => $this->queueKey($queued->area_id, $queued->ratings) === $queueKey)
            ->values();

        $index = $queue->search(fn (Training $queued) => $queued->is($training));

        if ($index === false) {
            return null;
        }

        return [
            'position' => $index + 1,
            'total' => $queue->count(),
            'name' => $this->queueName($training->ratings),
        ];
    }

    /**
     * Get the number of requests in every queue, keyed by queueKey().
     *
     * @return Collection<string, int>
     */
    public function queueSizes(): Collection
    {
        return $this->queuedTrainings()->countBy(
            fn (Training $training) => $this->queueKey($training->area_id, $training->ratings)
        );
    }

    /**
     * Get the key identifying the queue of the given area and ratings.
     *
     * @param  Collection<int, Rating>  $ratings
     */
    public function queueKey(int $areaId, Collection $ratings): string
    {
        return $areaId . ':' . $this->levelRatings($ratings)->pluck('id')->implode(',');
    }

    /**
     * Get the display name of the level of the given ratings.
     *
     * @param  Collection<int, Rating>  $ratings
     */
    public function queueName(Collection $ratings): string
    {
        return $this->levelRatings($ratings)->pluck('name')->implode(' + ');
    }

    /**
     * Get the trainings in queue, first in line first.
     *
     * @param  int|null  $areaId  limits the result to one area
     * @return Collection<int, Training>
     */
    private function queuedTrainings(?int $areaId = null): Collection
    {
        return Training::with('ratings')
            ->where('status', TrainingStatus::IN_QUEUE)
            ->when($areaId, fn ($query) => $query->where('area_id', $areaId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Reduce ratings to the ones that decide the queue.
     *
     * @param  Collection<int, Rating>  $ratings
     * @return Collection<int, Rating>
     */
    private function levelRatings(Collection $ratings): Collection
    {
        $highestVatsimRating = $ratings
            ->whereNotNull('vatsim_rating')
            ->sortByDesc(fn (Rating $rating) => $rating->vatsim_rating->value)
            ->first();

        return $highestVatsimRating
            ? collect([$highestVatsimRating])
            : $ratings->sortBy('id')->values();
    }
}
