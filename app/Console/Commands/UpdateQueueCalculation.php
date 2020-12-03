<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Country;
use App\Training;
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

        $countries = Country::all();
        foreach($countries as $country){
            foreach($country->ratings as $rating){
                
                $averageSum = 0;
                $averageNumber = 0;

                // Get the queue time from each training of this specific rating in the specific country
                foreach($rating->trainings->where('country_id', $country->id)->whereNotNull('created_at')->whereNotNull('started_at') as $training){

                    // Only include pure Vatsim ratings in calculation
                    if($training->ratings->count() == 1 && $training->ratings->first()->vatsim_rating){
                        if($training->status == -1){
                            $trainingCreated = $training->created_at;
                            $trainingStarted = $training->started_at;

                            // Calculate the difference in seconds with Carbon, then subtract the paused time if any.
                            $waitingTime = $trainingStarted->diffInSeconds($trainingCreated);
                            $waitingTime = $waitingTime - $training->paused_length;

                            // Inject this specific training's record into the average calculation
                            $averageSum = $averageSum + $waitingTime;
                            $averageNumber++;
                        }
                    }                    
                }   

                // Calculate the average for this country's selected rating, then insert it to the country's rating column
                if($averageNumber){
                    $average = $averageSum / $averageNumber;
                    $rating->pivot->queue_length = $average;
                    $rating->pivot->save();
                    $this->info($country->name.' '.$rating->name.' rating calculated average to '.round($average/60/60/24, 2).' days.');
                } else {
                    $rating->pivot->queue_length = NULL;
                }
            }
        }

        $this->info("Queue length calculations complete.");
    }
}
