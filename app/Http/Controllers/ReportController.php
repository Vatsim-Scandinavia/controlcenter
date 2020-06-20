<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Country;

class ReportController extends Controller
{
    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function trainings($country = false){

        // Note: Yes I know I could make this under same IF, but then it'd become a nice spaghetti!

        // Calculate total training requests
        

        // Calculate new requests last 6 months

        // Calculate completed requests last 6 months

        // Calculate queues
        $queues = [];
        if($country){
            $filter = Country::find($country)->name;

            foreach(Country::find($country)->ratings as $rating){
                if($rating->pivot->queue_length){
                    $queues[$rating->name] = $rating->pivot->queue_length;
                }
            }
        } else {
            $filter = 'All FIRs';

            $divideRating = [];
            foreach(Country::all() as $country){
                // Loop through the ratings of this country to get queue length
                foreach($country->ratings as $rating){

                    // Only calculate if queue length is defined
                    if($rating->pivot->queue_length){
                        if(isset($queues[$rating->name])){
                            $queues[$rating->name] = $queues[$rating->name] + $rating->pivot->queue_length;
                            $divideRating[$rating->name]++;
                        } else {
                            $queues[$rating->name] = $rating->pivot->queue_length;
                            $divideRating[$rating->name] = 1;
                        }
                    }
                }

            }

            // Divide the queue length appropriately to get an average across countries
            foreach($queues as $queue => $value){
                $queues[$queue] = $value / $divideRating[$queue];
            }

        }

        // Wrap it up and send it to the view
        $firs = Country::all();
        return view('reports.trainings', compact('filter', 'firs', 'queues'));
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function mentors(){
        return view('reports.mentors');
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function atc(){

        $controllers = User::all();

        return view('reports.atc', compact('controllers'));
    }
}
