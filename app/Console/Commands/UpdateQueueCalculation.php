<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Area;
use App\Models\Training;
use Carbon\Carbon;

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
        foreach($areas as $area){
            foreach($area->ratings as $rating){
                
                $averageData = [];

                // Get the queue time from each training of this specific rating in the specific area
                foreach($rating->trainings->where('area_id', $area->id)->whereNotNull('created_at')->whereNotNull('started_at') as $training){

                    // Only include pure Vatsim ratings in calculation
                    if($training->ratings->count() == 1 && $training->ratings->first()->vatsim_rating){
                        if($training->status == -1){
                            $trainingCreated = $training->created_at;
                            $trainingStarted = $training->started_at;

                            // Calculate the difference in seconds with Carbon, then subtract the paused time if any.
                            $waitingTime = $trainingStarted->diffInSeconds($trainingCreated);
                            $waitingTime = $waitingTime - $training->paused_length;

                            // Inject this specific training's record into the average calculation
                            array_push($averageData, $waitingTime);
                        }
                    }                    
                }   

                // Calculate the average for this area's selected rating, then insert it to the area's rating column. Only count if two or more trainings are complete to avoid logic errors.
                if(count($averageData) >= 2){

                    // Split the array into two low and high chunks
                    $halved = array_chunk($averageData, ceil(count($averageData)/2));
                    
                    $firstHalfAvg = array_sum($halved[0]) / count(array_filter($halved[0]));
                    $secondHalfAvg = array_sum($halved[1]) / count(array_filter($halved[1]));

                    $rating->pivot->queue_length_low = $firstHalfAvg;
                    $rating->pivot->queue_length_high = $secondHalfAvg;
                    $rating->pivot->save();

                    $this->info($area->name.' '.$rating->name.' rating calculated average from '.round($firstHalfAvg/60/60/24, 2).' to '.round($secondHalfAvg/60/60/24, 2).' days.');
                } else {
                    $rating->pivot->queue_length = NULL;
                }
            }
        }

        $this->info("Queue length calculations complete.");
    }
}
