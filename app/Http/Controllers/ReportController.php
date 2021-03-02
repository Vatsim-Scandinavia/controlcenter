<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\ManagementReport;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * This controller handles the report views and statistics
 */
class ReportController extends Controller
{
    /**
     * Show the training statistics view
     *
     * @param int $filterArea areaId to filter by
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function trainings($filterArea = false){

        $this->authorize('accessTrainingReports', ManagementReport::class);
        // Get stats
        $cardStats = $this->getCardStats($filterArea);
        $totalRequests = $this->getDailyRequestsStats($filterArea);
        list($newRequests, $completedRequests) = $this->getBiAnnualRequestsStats($filterArea);
        $queues = $this->getQueueStats($filterArea);

        // Send it to the view
        ($filterArea) ? $filterName = Area::find($filterArea)->name :  $filterName = 'All Areas';
        $areas = Area::all();

        return view('reports.trainings', compact('filterName', 'areas', 'cardStats', 'totalRequests', 'newRequests', 'completedRequests', 'queues'));
    }

    /**
     * Show the mentors statistics view
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function mentors(){

        $this->authorize('viewMentors', ManagementReport::class);

        if (auth()->user()->isAdmin()) {

            $mentors = Group::find(3)->users;

        } else {

            $mentors = Group::find(3)->users()->whereHas('groups', function(Builder $query) {
                $query->whereIn('area_id', auth()->user()->groups()->pluck('area_id'));
            })->get();

        }

        return view('reports.mentors', compact('mentors'));
    }

    /**
     * Show the atc active statistics view
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function atc(){

        $this->authorize('viewAtcActivity', ManagementReport::class);

        $controllers = User::all();

        return view('reports.atc', compact('controllers'));
    }


    /**
     * Return the statistics for the cards (in queue, in training, awaiting exam, completed this year) on top of the page
     *
     * @param int $filterArea areaId to filter by
     * @return mixed
     */
    protected function getCardStats($filterArea)
    {
        $payload = [
            "waiting" => 0,
            "training" => 0,
            "exam" => 0,
            "completed" => 0,
        ];

        if($filterArea){
            $payload["waiting"] = Area::find($filterArea)->trainings->where('status', 0)->count();
            $payload["training"] = Area::find($filterArea)->trainings->whereIn('status', [1, 2])->count();
            $payload["exam"] = Area::find($filterArea)->trainings->where('status', 3)->count();
            $payload["completed"] = Area::find($filterArea)->trainings->where('status', -1)->where('closed_at', '>=', date("Y-m-d H:i:s", strtotime('first day of january this year')))->count();
        } else {
            foreach(Area::all() as $area){
                $payload["waiting"] = $payload["waiting"] + $area->trainings->where('status', 0)->count();
                $payload["training"] = $payload["training"] + $area->trainings->whereIn('status', [1, 2])->count();
                $payload["exam"] = $payload["exam"] + $area->trainings->where('status', 3)->count();
                $payload["completed"] = $payload["completed"] + $area->trainings->where('status', -1)->where('closed_at', '>=', date("Y-m-d H:i:s", strtotime('first day of january this year')))->count();
            }
        }

        return $payload;
    }

    /**
     * Return the statistics the total amount of requests per day
     *
     * @param int $areaFilter areaId to filter by
     * @return mixed
     */
    protected function getDailyRequestsStats($areaFilter)
    {
        $payload = [];
        if($areaFilter){

            $data = Training::select([DB::raw('count(id) as `count`'), DB::raw('DATE(created_at) as day')])->groupBy('day')
              ->where('area_id', $areaFilter)
              ->where('created_at', '>=', Carbon::now()->subYear(1))
              ->get();

            foreach($data as $entry) {
                array_push($payload, ['t' => $entry->day, 'y' => $entry->count]);
            }

        } else {
            $data = Training::select([
                DB::raw('count(id) as `count`'),
                DB::raw('DATE(created_at) as day')
              ])->groupBy('day')
              ->where('created_at', '>=', Carbon::now()->subYear(1))
              ->get();

            foreach($data as $entry) {
                array_push($payload, ['t' => $entry->day, 'y' => $entry->count]);
            }
        }

        return $payload;
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param int $areaFilter areaId to filter by
     * @return mixed
     */
    protected function getBiAnnualRequestsStats($areaFilter)
    {
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

        if($areaFilter){

            foreach(Rating::all() as $rating){
                if($rating->id >= 2){

                    $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
                    $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

                    // New requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.created_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                        ->where('created_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->where('area_id', $areaFilter)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $newRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }

                    // Completed requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.closed_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                        ->where('status', -1)
                        ->where('closed_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->where('area_id', $areaFilter)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $completedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }
                }
            }

        } else {

            foreach(Rating::all() as $rating){
                if($rating->id >= 2){

                    $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
                    $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

                    // New requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.created_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                        ->where('created_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $newRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }

                    // Completed requests
                    $query = DB::table('trainings')
                        ->select(DB::raw('count(trainings.id) as `count`'), DB::raw('MONTH(trainings.closed_at) as month'))
                        ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                        ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                        ->where('status', -1)
                        ->where('closed_at', '>=', date("Y-m-d H:i:s", strtotime('-6 months')))
                        ->where('rating_id', $rating->id)
                        ->groupBy('month')
                        ->get();

                    foreach($query as $entry) {
                        $completedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
                    }
                }
            }

        }

        return [$newRequests, $completedRequests];
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param int $areaFilter areaId to filter by
     * @return mixed
     */
    protected function getQueueStats($areaFilter)
    {
        $payload = [];
        if($areaFilter){
            foreach(Area::find($areaFilter)->ratings as $rating){
                if($rating->pivot->queue_length){
                    $payload[$rating->name] = $rating->pivot->queue_length;
                }
            }
        } else {

            $divideRating = [];
            foreach(Area::all() as $area){
                // Loop through the ratings of this area to get queue length
                foreach($area->ratings as $rating){

                    // Only calculate if queue length is defined
                    if($rating->pivot->queue_length){
                        if(isset($payload[$rating->name])){
                            $payload[$rating->name] = $payload[$rating->name] + $rating->pivot->queue_length;
                            $divideRating[$rating->name]++;
                        } else {
                            $payload[$rating->name] = $rating->pivot->queue_length;
                            $divideRating[$rating->name] = 1;
                        }
                    }
                }

            }

            // Divide the queue length appropriately to get an average across areas
            foreach($payload as $queue => $value){
                $payload[$queue] = $value / $divideRating[$queue];
            }

        }

        return $payload;
    }

}
