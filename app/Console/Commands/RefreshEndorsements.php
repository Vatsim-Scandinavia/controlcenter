<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Training;
use App\TrainingExamination;

class RefreshEndorsements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:endorsements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes all local endorsements based on all recorded trainings';

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
     * @return mixed
     */
    public function handle()
    {
        $trainings = Training::where('status', -1)->get();
        
        foreach($trainings as $training){

            if(TrainingExamination::where('result', '=', 'PASSED')->where('training_id', $training->id)->exists()){

                foreach($training->ratings as $rating){
                    if($rating->vatsim_rating == null){
                        $training->user->ratings()->syncWithoutDetaching($rating->id);
                    }
                }
            }

        }

        $this->info('Endorsement list refreshed.');
        
    }
}
