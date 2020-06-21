<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\User;
use App\Country;
use App\Training;
use App\Rating;
use DateTime;

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

            $data = Training::select([
                // This aggregates the data and makes available a 'count' attribute
                DB::raw('count(id) as `count`'), 
                // This throws away the timestamp portion of the date
                DB::raw('DATE(created_at) as day')
              // Group these records according to that day
              ])->groupBy('day')
              // Show only the filtered country
              ->where('country_id', $filterCountry)
              // And restrict these results to only those created in the last week
              ->where('created_at', '>=', Carbon::now()->subYear(1))
              ->get()
            ;

            foreach($data as $entry) {
                array_push($totalRequests, ['t' => $entry->day, 'y' => $entry->count]);
            }

        } else {
            $data = Training::select([
                DB::raw('count(id) as `count`'), 
                // This throws away the timestamp portion of the date
                DB::raw('DATE(created_at) as day')
              // Group these records according to that day
              ])->groupBy('day')
              // And restrict these results to only those created in the last week
              ->where('created_at', '>=', Carbon::now()->subYear(1))
              ->get()
            ;

            foreach($data as $entry) {
                array_push($totalRequests, ['t' => $entry->day, 'y' => $entry->count]);
            }
        }

        // Calculate new and requests last 6 months
        $monthTranslator = [
            (int)Carbon::now()->format('m') => 6,
            (int)Carbon::now()->subMonths(1)->format('m') => 5,
            (int)Carbon::now()->subMonths(2)->format('m') => 4,
            (int)Carbon::now()->subMonths(3)->format('m') => 3,
            (int)Carbon::now()->subMonths(4)->format('m') => 2,
            (int)Carbon::now()->subMonths(5)->format('m') => 1,
            (int)Carbon::now()->subMonths(6)->format('m') => 0,
        ];

        $newRequests = [];
        $completedRequests = [];
        if($filterCountry){

            foreach(Rating::all() as $rating){
                if($rating->vatsim_rating && $rating->id >= 2 && $rating->id <= 4){

                    $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
                    $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

                    // New requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.created_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.training_id')
                        ->where('created_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->where('country_id', $filterCountry)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $newRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }

                    // Completed requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.finished_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.training_id')
                        ->where('status', 3)
                        ->where('finished_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->where('country_id', $filterCountry)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $completedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }

                    //dd($newRequests, $completedRequests);
                }
            }

        } else {

        }


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

        return view('reports.trainings', compact('filterName', 'firs', 'cardNumbers', 'totalRequests', 'newRequests', 'completedRequests', 'queues'));
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
