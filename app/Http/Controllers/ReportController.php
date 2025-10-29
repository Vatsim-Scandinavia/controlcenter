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

        $users = User::has('groups')->get();

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

        $feedback = Feedback::latest()->get();

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
        $firstDayOfYear = now()->startOfYear();

        $query = Training::query();

        if ($filterArea) {
            $query->where('area_id', $filterArea);
        }

        $stats = $query->selectRaw("
                COUNT(CASE WHEN status = 0 THEN 1 END) as waiting,
                COUNT(CASE WHEN status IN (1, 2) THEN 1 END) as training,
                COUNT(CASE WHEN status = 3 THEN 1 END) as exam,
                COUNT(CASE WHEN status = -1 AND closed_at >= ? THEN 1 END) as completed,
                COUNT(CASE WHEN status = -2 AND closed_at >= ? THEN 1 END) as closed
            ", [$firstDayOfYear, $firstDayOfYear])
            ->first();

        return [
            'waiting' => $stats->waiting,
            'training' => $stats->training,
            'exam' => $stats->exam,
            'completed' => $stats->completed,
            'closed' => $stats->closed,
        ];
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
        $monthTranslator = $this->getBiAnnualMonthTranslator();
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();

        // Initialize arrays for each rating
        $ratings = Rating::all();
        $newRequests = [];
        $completedRequests = [];
        $closedRequests = [];
        foreach ($ratings as $rating) {
            $newRequests[$rating->name] = array_fill(0, 7, 0);
            $completedRequests[$rating->name] = array_fill(0, 7, 0);
            $closedRequests[$rating->name] = array_fill(0, 7, 0);
        }

        // Fetch and process training data in a single query
        $trainingsQuery = Training::with('ratings')
            ->where(function ($query) use ($sixMonthsAgo) {
                $query->where('created_at', '>=', $sixMonthsAgo)
                    ->orWhere(function ($subQuery) use ($sixMonthsAgo) {
                        $subQuery->whereIn('status', [-1, -2])
                            ->where('closed_at', '>=', $sixMonthsAgo);
                    });
            });

        if ($areaFilter) {
            $trainingsQuery->where('area_id', $areaFilter);
        }

        foreach ($trainingsQuery->cursor() as $training) {
            foreach ($training->ratings as $rating) {
                if (! isset($newRequests[$rating->name])) {
                    continue;
                }

                // New requests
                if ($training->created_at >= $sixMonthsAgo) {
                    $month = $monthTranslator[(int) $training->created_at->format('m')] ?? null;
                    if ($month !== null) {
                        $newRequests[$rating->name][$month]++;
                    }
                }

                // Completed and closed requests
                if ($training->closed_at >= $sixMonthsAgo) {
                    $month = $monthTranslator[(int) $training->closed_at->format('m')] ?? null;
                    if ($month !== null) {
                        if ($training->status == -1) {
                            $completedRequests[$rating->name][$month]++;
                        } elseif ($training->status == -2) {
                            $closedRequests[$rating->name][$month]++;
                        }
                    }
                }
            }
        }

        // Fetch and process examination data
        $passFailRequests = ['Passed' => array_fill(0, 7, 0), 'Failed' => array_fill(0, 7, 0)];
        $examinationsQuery = DB::table('training_examinations')
            ->select(DB::raw('count(training_examinations.id) as count'), DB::raw(Sql::month('training_examinations.examination_date', 'month')), 'result')
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
            ->whereIn('result', ['PASSED', 'FAILED'])
            ->where('examination_date', '>=', $sixMonthsAgo)
            ->groupBy('month', 'result');

        if ($areaFilter) {
            $examinationsQuery->where('trainings.area_id', $areaFilter);
        }

        $examinations = $examinationsQuery->get();

        foreach ($examinations as $entry) {
            if (isset($monthTranslator[$entry->month])) {
                $passFailRequests[$entry->result][$monthTranslator[$entry->month]] = $entry->count;
            }
        }

        // Remove series that have all 0 values across all charts.
        $nonZeroSeries = [];
        $charts = [$newRequests, $completedRequests, $closedRequests];
        foreach ($charts as $chart) {
            foreach ($chart as $seriesName => $seriesData) {
                if (! in_array($seriesName, $nonZeroSeries) && array_sum($seriesData) > 0) {
                    $nonZeroSeries[] = $seriesName;
                }
            }
        }

        $filterAllSeries = function ($chart) use ($nonZeroSeries) {
            return array_filter($chart, fn ($seriesName) => in_array($seriesName, $nonZeroSeries), ARRAY_FILTER_USE_KEY);
        };

        return [
            $filterAllSeries($newRequests),
            $filterAllSeries($completedRequests),
            $filterAllSeries($closedRequests),
            $passFailRequests,
        ];
    }

    /**
     * Get the month translator for the bi-annual statistics.
     *
     * @return array
     */
    protected function getBiAnnualMonthTranslator(): array
    {
        $translator = [];
        for ($i = 6; $i >= 0; $i--) {
            $translator[(int) now()->subMonthsNoOverflow($i)->startOfMonth()->format('m')] = 6 - $i;
        }

        return $translator;
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
            foreach (Area::with('ratings')->get() as $area) {
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
