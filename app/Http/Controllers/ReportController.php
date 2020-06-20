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

    public function trainings($filterCountry = false){
        // Note: Yes I know I could make this under same IF, but then it'd become a nice spaghetti!
        // I'll split it up into individual functions once it works.

        // Calculate card numbers (in queue, in training, awaiting exam, completed this year)
        $cardNumbers = [
            "waiting" => 0,
            "training" => 0,
            "exam" => 0,
            "completed" => 0,
        ];
        if($filterCountry){

            $cardNumbers["waiting"] = Country::find($filterCountry)->trainings->where('status', 0)->count();
            $cardNumbers["training"] = Country::find($filterCountry)->trainings->where('status', 1)->count();
            $cardNumbers["exam"] = Country::find($filterCountry)->trainings->where('status', 2)->count();
            $cardNumbers["completed"] = Country::find($filterCountry)->trainings->where('status', 3)->where('finished_at', '>=', date("Y-m-d H:i:s", strtotime('first day of january this year')))->count();

        } else {
            
            foreach(Country::all() as $country){
                $cardNumbers["waiting"] = $cardNumbers["waiting"] + $country->trainings->where('status', 0)->count();
                $cardNumbers["training"] = $cardNumbers["training"] + $country->trainings->where('status', 1)->count();
                $cardNumbers["exam"] = $cardNumbers["exam"] + $country->trainings->where('status', 2)->count();
                $cardNumbers["completed"] = $cardNumbers["completed"] + $country->trainings->where('status', 3)->where('finished_at', '>=', date("Y-m-d H:i:s", strtotime('first day of january this year')))->count();
            }

        }


        // Calculate total training requests
        $totalRequests = [];
        if($filterCountry){

            //dd(Country::find($country)->trainings->where('created_at', '<=', date('Y-m-d H:i:s')));

        } else {
            
        }

        // Calculate new requests last 6 months

        // Calculate completed requests last 6 months


        // Calculate queues
        $queues = [];
        if($filterCountry){
            foreach(Country::find($filterCountry)->ratings as $rating){
                if($rating->pivot->queue_length){
                    $queues[$rating->name] = $rating->pivot->queue_length;
                }
            }
        } else {

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
        ($filterCountry) ? $filterName = Country::find($filterCountry)->name :  $filterName = 'All FIRs';
        $firs = Country::all();

        return view('reports.trainings', compact('filterName', 'firs', 'cardNumbers', 'queues'));
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
