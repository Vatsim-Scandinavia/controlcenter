<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\ManagementReport;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Services\Sql\Sql;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function access()
    {
        $this->authorize('viewAccessReport', ManagementReport::class);

        $availableUsers = User::all();

        // Cherrypick those with access roles
        $users = collect();
        foreach ($availableUsers as $user) {
            if ($user->groups()->count()) {
                $users->push($user);
            }
        }

        $areas = Area::all();

        return view('reports.access', compact('users', 'areas'));
    }

    /**
     * Show the training statistics view
     *
     * @param  false|int  $filterArea  areaId to filter by
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function trainings(false|int $filterArea = false)
    {
        $this->authorize('accessTrainingReports', [ManagementReport::class, $filterArea]);
        // Get stats
        $cardStats = $this->getCardStats($filterArea);
        $totalRequests = $this->getDailyRequestsStats($filterArea);
        [$newRequests, $completedRequests, $closedRequests, $passFailRequests] = $this->getBiAnnualRequestsStats($filterArea);
        $queues = $this->getQueueStats($filterArea);

        // Send it to the view
        ($filterArea) ? $filterName = Area::find($filterArea)->name : $filterName = 'All Areas';
        $areas = Area::all();

        return view('reports.trainings', compact('filterName', 'areas', 'cardStats', 'totalRequests', 'newRequests', 'completedRequests', 'closedRequests', 'passFailRequests', 'queues'));
    }

    /**
     * Show the training activities statistics view
     *
     * @param  int  $filterArea  areaId to filter by
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function activities($filterArea = false)
    {
        $this->authorize('accessTrainingReports', [ManagementReport::class, $filterArea]);

        // Fetch TrainingActivity
        if ($filterArea) {
            $activities = TrainingActivity::with('training', 'training.ratings', 'training.user', 'user', 'endorsement')->orderByDesc('created_at')->whereHas('training', function (Builder $q) use ($filterArea) {
                $q->where('area_id', $filterArea);
            })->limit(100)->get();

            // Fetch TrainingReport and ExaminationReport if activities exist
            if ($activities->count() > 0) {
                $trainingReports = TrainingReport::where('created_at', '>=', $activities->last()->created_at)->where('draft', false)->whereHas('training', function (Builder $q) use ($filterArea) {
                    $q->where('area_id', $filterArea);
                })->get();

                $examinationReports = TrainingExamination::where('created_at', '>=', $activities->last()->created_at)->whereHas('training', function (Builder $q) use ($filterArea) {
                    $q->where('area_id', $filterArea);
                })->get();
            }
        } else {
            // The training reports will use updated_at so draft publishing date is correct.
            $activities = TrainingActivity::with('training', 'training.ratings', 'training.user', 'user', 'endorsement')->orderByDesc('created_at')->limit(100)->get();
            $trainingReports = TrainingReport::where('updated_at', '>=', $activities->last()->updated_at)->where('draft', false)->get();
            $examinationReports = TrainingExamination::where('created_at', '>=', $activities->last()->created_at)->get();
        }

        if (isset($trainingReports) && isset($examinationReports)) {
            $entries = $trainingReports->concat($examinationReports);
            $entries = $entries->concat($activities);
        } elseif (isset($trainingReports)) {
            $entries = $trainingReports->concat($activities);
        } elseif (isset($examinationReports)) {
            $entries = $examinationReports->concat($activities);
        } else {
            $entries = $activities;
        }

        // Do the rest
        $entries = $entries->sortByDesc('created_at');
        $statuses = TrainingController::$statuses;

        ($filterArea) ? $filterName = Area::find($filterArea)->name : $filterName = 'All Areas';
        $areas = Area::all();

        return view('reports.activities', compact('entries', 'statuses', 'filterName', 'areas'));
    }

    /**
     * Show the mentors statistics view
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function mentors()
    {
        $this->authorize('viewMentors', ManagementReport::class);

        if (auth()->user()->isAdmin()) {
            $mentors = Group::find(3)->users()->with('trainingReports', 'teaches', 'teaches.reports', 'teaches.user')->get();
        } else {
            $mentors = Group::find(3)->users()->with('trainingReports', 'teaches', 'teaches.reports', 'teaches.user')->whereHas('groups', function (Builder $query) {
                $query->whereIn('area_id', auth()->user()->groups()->pluck('area_id'));
            })->get();
        }

        $mentors = $mentors->sortBy('name')->unique();
        $statuses = TrainingController::$statuses;

        return view('reports.mentors', compact('mentors', 'statuses'));
    }

    /**
     * Index received feedback
     *
     * @return \Illuminate\View\View
     */
    public function feedback()
    {
        $this->authorize('viewFeedback', ManagementReport::class);

        $feedback = Feedback::all()->sortByDesc('created_at');

        return view('reports.feedback', compact('feedback'));
    }

    /**
     * Return the statistics for the cards (in queue, in training, awaiting exam, completed this year) on top of the page
     *
     * @param  int  $filterArea  areaId to filter by
     * @return mixed
     */
    protected function getCardStats($filterArea)
    {
        $payload = [
            'waiting' => 0,
            'training' => 0,
            'exam' => 0,
            'completed' => 0,
            'closed' => 0,
        ];

        if ($filterArea) {
            $payload['waiting'] = Area::find($filterArea)->trainings->where('status', 0)->count();
            $payload['training'] = Area::find($filterArea)->trainings->whereIn('status', [1, 2])->count();
            $payload['exam'] = Area::find($filterArea)->trainings->where('status', 3)->count();
            $payload['completed'] = Area::find($filterArea)->trainings->where('status', -1)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
            $payload['closed'] = Area::find($filterArea)->trainings->where('status', -2)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
        } else {
            foreach (Area::all() as $area) {
                $payload['waiting'] = $payload['waiting'] + $area->trainings->where('status', 0)->count();
                $payload['training'] = $payload['training'] + $area->trainings->whereIn('status', [1, 2])->count();
                $payload['exam'] = $payload['exam'] + $area->trainings->where('status', 3)->count();
                $payload['completed'] = $payload['completed'] + $area->trainings->where('status', -1)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
                $payload['closed'] = $payload['closed'] + $area->trainings->where('status', -2)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
            }
        }

        return $payload;
    }

    /**
     * Return the statistics the total amount of requests per day
     *
     * @param  false|int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getDailyRequestsStats(false|int $areaFilter)
    {
        // Create an arra with all dates last 12 months
        $dates = [];
        foreach (CarbonPeriod::create(Carbon::now()->subYear(1), Carbon::now()) as $date) {
            $dates[$date->format('Y-m-d')] = ['x' => $date->format('Y-m-d'), 'y' => 0];
        }

        // Fill the array
        $data = Training::select([
            DB::raw(Sql::as('count(id)', 'count')),
            DB::raw('DATE(created_at) as day'),
        ])->groupBy('day')
            ->where('created_at', '>=', Carbon::now()->subYear(1));

        if ($areaFilter) {
            $data->where('area_id', $areaFilter);
        }
        $data = $data->get();

        foreach ($data as $entry) {
            $dates[$entry->day]['y'] = $entry->count;
        }

        // Strip the keys to match requirement of chart.js
        $payload = [];
        foreach ($dates as $loadKey => $load) {
            array_push($payload, $load);
        }

        return $payload;
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param  false|int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getBiAnnualRequestsStats(false|int $areaFilter)
    {
        $monthTranslator = [
            (int) Carbon::now()->startOfMonth()->format('m') => 6,
            (int) Carbon::now()->subMonthsNoOverflow(1)->startOfMonth()->format('m') => 5,
            (int) Carbon::now()->subMonthsNoOverflow(2)->startOfMonth()->format('m') => 4,
            (int) Carbon::now()->subMonthsNoOverflow(3)->startOfMonth()->format('m') => 3,
            (int) Carbon::now()->subMonthsNoOverflow(4)->startOfMonth()->format('m') => 2,
            (int) Carbon::now()->subMonthsNoOverflow(5)->startOfMonth()->format('m') => 1,
            (int) Carbon::now()->subMonthsNoOverflow(6)->startOfMonth()->format('m') => 0,
        ];

        $newRequests = [];
        $completedRequests = [];
        $closedRequests = [];
        $passFailRequests = [];

        foreach (Rating::all() as $rating) {
            $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $closedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $passFailRequests['Passed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $passFailRequests['Failed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

            // New requests
            $query = DB::table('trainings')
                ->select(DB::raw(Sql::as('count(trainings.id)', 'count')), DB::raw(Sql::month('trainings.created_at', 'month')))
                ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->where('rating_id', $rating->id);

            if ($areaFilter) {
                $query->where('area_id', $areaFilter);
            }

            $query = $query->groupBy('month')->get();

            foreach ($query as $entry) {
                $newRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }

            // Completed requests
            $query = DB::table('trainings')
                ->select(DB::raw(Sql::as('count(trainings.id)', 'count')), DB::raw(Sql::month('trainings.closed_at', 'month')))
                ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                ->where('status', -1)
                ->where('closed_at', '>=', now()->subMonths(6)->startOfMonth())
                ->where('rating_id', $rating->id);

            if ($areaFilter) {
                $query->where('area_id', $areaFilter);
            }

            $query = $query->groupBy('month')->get();

            foreach ($query as $entry) {
                $completedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }

            // Closed requests
            $query = DB::table('trainings')
                ->select(DB::raw(Sql::as('count(trainings.id)', 'count')), DB::raw(Sql::month('trainings.closed_at', 'month')))
                ->join('rating_training', 'trainings.id', '=', 'rating_training.training_id')
                ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
                ->where('status', -2)
                ->where('closed_at', '>=', now()->subMonths(6)->startOfMonth())
                ->where('rating_id', $rating->id);

            if ($areaFilter) {
                $query->where('area_id', $areaFilter);
            }

            $query = $query->groupBy('month')->get();

            foreach ($query as $entry) {
                $closedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }
        }

        // Passed trainings
        $query = DB::table('training_examinations')
            ->select(DB::raw(Sql::as('count(training_examinations.id)', 'count')), DB::raw(Sql::month('training_examinations.examination_date', 'month')))
            ->join('trainings', 'trainings.id', '=', 'training_examinations.training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rating_training')
                    ->join('ratings', 'ratings.id', 'rating_training.rating_id')
                    ->whereColumn('rating_training.training_id', 'trainings.id')
                    ->where('ratings.vatsim_rating', '>=', 3)
                    ->whereNotNull('ratings.vatsim_rating');
            })
            ->whereIn('trainings.type', [1, 4])
            ->where('result', 'PASSED')
            ->where('examination_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Passed'][$monthTranslator[$entry->month]] = $entry->count;
        }

        // Failed trainings
        $query = DB::table('training_examinations')
            ->select(DB::raw(Sql::as('count(training_examinations.id)', 'count')), DB::raw(Sql::month('training_examinations.examination_date', 'month')))
            ->join('trainings', 'trainings.id', '=', 'training_examinations.training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rating_training')
                    ->join('ratings', 'ratings.id', 'rating_training.rating_id')
                    ->whereColumn('rating_training.training_id', 'trainings.id')
                    ->where('ratings.vatsim_rating', '>=', 3)
                    ->whereNotNull('ratings.vatsim_rating');
            })
            ->whereIn('trainings.type', [1, 4])
            ->where('result', 'FAILED')
            ->where('examination_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Failed'][$monthTranslator[$entry->month]] = $entry->count;
        }

        // Remove series that all 0 values across all charts.
        $nonZeroSeries = [];

        $charts = [$newRequests, $completedRequests, $closedRequests];
        foreach ($charts as $chart) {
            foreach ($chart as $seriesName => $seriesData) {
                if (! in_array($seriesName, $nonZeroSeries)) {
                    foreach ($seriesData as $dataPoint) {
                        if ($dataPoint > 0) {
                            $nonZeroSeries[] = $seriesName;
                            break;
                        }
                    }
                }
            }
        }

        $filterAllSeries = function ($chart) use ($nonZeroSeries) {
            foreach ($chart as $seriesName => $seriesData) {
                if (! in_array($seriesName, $nonZeroSeries)) {
                    unset($chart[$seriesName]);
                }
            }

            return $chart;
        };

        return [
            $filterAllSeries($newRequests),
            $filterAllSeries($completedRequests),
            $filterAllSeries($closedRequests),
            $passFailRequests,
        ];
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param  false|int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getQueueStats(false|int $areaFilter)
    {
        $payload = [];
        if ($areaFilter) {
            foreach (Area::find($areaFilter)->ratings as $rating) {
                if ($rating->pivot->queue_length_low && $rating->pivot->queue_length_high) {
                    $payload[$rating->name] = [
                        $rating->pivot->queue_length_low,
                        $rating->pivot->queue_length_high,
                    ];
                }
            }
        } else {
            $divideRating = [];
            foreach (Area::all() as $area) {
                // Loop through the ratings of this area to get queue length
                foreach ($area->ratings as $rating) {
                    // Only calculate if queue length is defined
                    if ($rating->pivot->queue_length_low && $rating->pivot->queue_length_high) {
                        if (isset($payload[$rating->name])) {
                            $payload[$rating->name][0] = $payload[$rating->name][0] + $rating->pivot->queue_length_low;
                            $payload[$rating->name][1] = $payload[$rating->name][1] + $rating->pivot->queue_length_high;
                            $divideRating[$rating->name]++;
                        } else {
                            $payload[$rating->name] = [
                                $rating->pivot->queue_length_low,
                                $rating->pivot->queue_length_high,
                            ];
                            $divideRating[$rating->name] = 1;
                        }
                    }
                }
            }

            // Divide the queue length appropriately to get an average across areas
            foreach ($payload as $queue => $value) {
                $payload[$queue][0] = $value[0] / $divideRating[$queue];
                $payload[$queue][1] = $value[1] / $divideRating[$queue];
            }
        }

        return $payload;
    }
}
