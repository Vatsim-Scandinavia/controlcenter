<?php

namespace App\Console\Commands;

use App\Helpers\TrainingStatus;
use App\Models\Area;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateQueueCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:queuecalculation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update queue calculations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $areas = Area::all();
        foreach ($areas as $area) {
            foreach ($area->ratings as $rating) {
                $averageData = [];

                // Skip endorsement traings
                if ($rating->vatsim_rating == null) {
                    continue;
                }

                // Get the queue time from each training of this specific rating in the specific area
                foreach ($rating->trainings->where('area_id', $area->id)->whereNotNull('created_at')->whereNull('paused_at') as $training) {
                    // Include training with GRP ratings inside
                    if ($training->ratings->count() >= 1 && $training->ratings->first()->vatsim_rating) {
                        if ($training->status == TrainingStatus::IN_QUEUE->value) {
                            $trainingCreated = $training->created_at;

                            // Calculate the difference in seconds with Carbon, then subtract the paused time if any.
                            $waitingTime = $trainingCreated->diffInSeconds(Carbon::now(), true);
                            $waitingTime = $waitingTime - $training->paused_length;

                            // Inject this specific training's record into the average calculation
                            array_push($averageData, $waitingTime);
                        }
                    }
                }

                // Calculate the average for this area's selected rating, then insert it to the area's rating column. Only count if two or more trainings are complete to avoid logic errors.
                if (count($averageData) >= 4) {
                    // Sort the array from low to high
                    sort($averageData);

                    // Split the array into two low and high chunks, we'll then only use the second half as it's the most representative
                    $halved = array_chunk($averageData, ceil(count($averageData) / 2));

                    $halvedHalved = array_chunk($halved[1], ceil(count($halved[1]) / 2));

                    $firstHalfAvg = array_sum($halvedHalved[0]) / count(array_filter($halvedHalved[0]));
                    $secondHalfAvg = array_sum($halvedHalved[1]) / count(array_filter($halvedHalved[1]));

                    $rating->pivot->queue_length_low = $firstHalfAvg;
                    $rating->pivot->queue_length_high = $secondHalfAvg;
                    $rating->pivot->save();

                    $this->info($area->name . ' ' . $rating->name . ' rating calculated average from ' . round($firstHalfAvg / 60 / 60 / 24, 2) . ' to ' . round($secondHalfAvg / 60 / 60 / 24, 2) . ' days.');
                } else {
                    $rating->pivot->queue_length_low = null;
                    $rating->pivot->queue_length_high = null;
                    $rating->pivot->save();
                }
            }
        }

        $this->info('Queue length calculations complete.');
    }
}
