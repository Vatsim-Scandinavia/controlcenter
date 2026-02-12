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

        $startDate = request()->filled('start_date') ? Carbon::parse(request()->input('start_date'))->startOfDay() : null;
        $endDate = request()->filled('end_date') ? Carbon::parse(request()->input('end_date'))->endOfDay() : null;

        $areas = Area::all();
        $filterName = 'All Areas';
        if ($filterArea) {
            $area = $areas->find($filterArea);
            if ($area) {
                $filterName = $area->name;
            }
        }

        [$newRequests, $completedRequests, $closedRequests, $passFailRequests, $labels] = $this->getBiAnnualRequestsStats($filterArea, $startDate, $endDate);

        return view('reports.trainings', [
            'filterName' => $filterName,
            'areas' => $areas,
            'cardStats' => $this->getCardStats($filterArea, $startDate, $endDate),
            'totalRequests' => $this->getDailyRequestsStats($filterArea, $startDate, $endDate),
            'newRequests' => $newRequests,
            'completedRequests' => $completedRequests,
            'closedRequests' => $closedRequests,
            'passFailRequests' => $passFailRequests,
            'queues' => $this->getQueueStats($filterArea),
            'labels' => $labels,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
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

        $activities = TrainingActivity::with('training', 'training.ratings', 'training.user', 'user', 'endorsement')
            ->when($filterArea, function (Builder $query, $filterArea) {
                $query->whereHas('training', fn (Builder $q) => $q->where('area_id', $filterArea));
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $entries = collect();
        if ($activities->isNotEmpty()) {
            $trainingReports = TrainingReport::where('draft', false)
                ->when($filterArea, function (Builder $query, $filterArea) {
                    $query->whereHas('training', fn (Builder $q) => $q->where('area_id', $filterArea));
                })
                ->where($filterArea ? 'created_at' : 'updated_at', '>=', $filterArea ? $activities->last()->created_at : $activities->last()->updated_at)
                ->get();

            $examinationReports = TrainingExamination::where('created_at', '>=', $activities->last()->created_at)
                ->when($filterArea, function (Builder $query, $filterArea) {
                    $query->whereHas('training', fn (Builder $q) => $q->where('area_id', $filterArea));
                })
                ->get();

            $entries = $entries->concat($trainingReports)->concat($examinationReports);
        }

        $entries = $entries->concat($activities)->sortByDesc('created_at');
        $statuses = TrainingController::$statuses;

        $areas = Area::all();
        $filterName = $filterArea ? $areas->find($filterArea)->name : 'All Areas';

        return view('reports.activities', [
            'entries' => $entries,
            'statuses' => $statuses,
            'filterName' => $filterName,
            'areas' => $areas,
        ]);
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
    protected function getCardStats($filterArea, $startDate = null, $endDate = null)
    {
        $start = $startDate ?? now()->startOfYear();
        $end = $endDate ?? now()->endOfDay();

        $query = Training::query();

        if ($filterArea) {
            $query->where('area_id', $filterArea);
        }

        $stats = $query->selectRaw('
                COUNT(CASE WHEN status = 0 THEN 1 END) as waiting,
                COUNT(CASE WHEN status IN (1, 2) THEN 1 END) as training,
                COUNT(CASE WHEN status = 3 THEN 1 END) as exam,
                COUNT(CASE WHEN status = -1 AND closed_at >= ? AND closed_at <= ? THEN 1 END) as completed,
                COUNT(CASE WHEN status = -2 AND closed_at >= ? AND closed_at <= ? THEN 1 END) as closed
            ', [$start, $end, $start, $end])
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
    protected function getDailyRequestsStats(false|int $areaFilter, $startDate = null, $endDate = null)
    {
        $start = $startDate ?? Carbon::now()->subYear(1);
        $end = $endDate ?? Carbon::now()->endOfDay();

        // Create an arra with all dates last 12 months
        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dates[$date->format('Y-m-d')] = ['x' => $date->format('Y-m-d'), 'y' => 0];
        }

        // Fill the array
        $data = Training::select([
            DB::raw(Sql::as('count(id)', 'count')),
            DB::raw('DATE(created_at) as day'),
        ])->groupBy('day')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);

        if ($areaFilter) {
            $data->where('area_id', $areaFilter);
        }
        $data = $data->get();

        foreach ($data as $entry) {
            if (isset($dates[$entry->day])) {
                $dates[$entry->day]['y'] = $entry->count;
            }
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
    protected function getBiAnnualRequestsStats(false|int $areaFilter, $startDate = null, $endDate = null)
    {
        $start = $startDate ?? now()->subMonths(6)->startOfMonth();
        $end = $endDate ?? now()->endOfDay();

        // Initialize translator and labels
        $monthTranslator = [];
        $labels = [];
        $index = 0;
        // Group by month
        $period = CarbonPeriod::create($start, '1 month', $end);

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $monthTranslator[$key] = $index;
            $labels[] = $date->format('F Y');
            $index++;
        }
        $totalBuckets = count($labels);

        // Initialize arrays for each rating
        $ratings = Rating::all();
        $newRequests = [];
        $completedRequests = [];
        $closedRequests = [];
        foreach ($ratings as $rating) {
            $newRequests[$rating->name] = array_fill(0, $totalBuckets, 0);
            $completedRequests[$rating->name] = array_fill(0, $totalBuckets, 0);
            $closedRequests[$rating->name] = array_fill(0, $totalBuckets, 0);
        }

        // Fetch and process training data in a single query
        $trainingsQuery = Training::with('ratings')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->whereIn('status', [-1, -2])
                            ->whereBetween('closed_at', [$start, $end]);
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
                if ($training->created_at >= $start && $training->created_at <= $end) {
                    $key = $training->created_at->format('Y-m');
                    if (isset($monthTranslator[$key])) {
                        $newRequests[$rating->name][$monthTranslator[$key]]++;
                    }
                }

                // Completed and closed requests
                if ($training->closed_at && $training->closed_at >= $start && $training->closed_at <= $end) {
                    $key = $training->closed_at->format('Y-m');
                    if (isset($monthTranslator[$key])) {
                        if ($training->status == -1) {
                            $completedRequests[$rating->name][$monthTranslator[$key]]++;
                        } elseif ($training->status == -2) {
                            $closedRequests[$rating->name][$monthTranslator[$key]]++;
                        }
                    }
                }
            }
        }

        // Fetch and process examination data
        $passFailRequests = ['PASSED' => array_fill(0, $totalBuckets, 0), 'FAILED' => array_fill(0, $totalBuckets, 0)];
        $examinationsQuery = DB::table('training_examinations')
            ->select(DB::raw('count(training_examinations.id) as count'), DB::raw(Sql::date('training_examinations.examination_date', 'exam_date')), 'result')
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
            ->whereBetween('examination_date', [$start, $end])
            ->groupBy('exam_date', 'result');

        if ($areaFilter) {
            $examinationsQuery->where('trainings.area_id', $areaFilter);
        }

        $examinations = $examinationsQuery->get();

        foreach ($examinations as $entry) {
            $date = Carbon::parse($entry->exam_date);
            $key = $date->format('Y-m');

            if (isset($monthTranslator[$key])) {
                $passFailRequests[$entry->result][$monthTranslator[$key]] += $entry->count;
            }
        }

        // Remove series that have all 0 values across all charts.
        $nonZeroSeriesNames = collect($newRequests)->keys()->filter(
            fn ($series) => array_sum($newRequests[$series]) > 0
                || array_sum($completedRequests[$series]) > 0
                || array_sum($closedRequests[$series]) > 0
        );

        $filter = fn ($chart) => collect($chart)->only($nonZeroSeriesNames)->all();

        return [
            $filter($newRequests),
            $filter($completedRequests),
            $filter($closedRequests),
            $passFailRequests,
            $labels,
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
        if ($areaFilter) {
            $area = Area::with('ratings')->find($areaFilter);
            $payload = [];
            foreach ($area->ratings as $rating) {
                if ($rating->pivot->queue_length_low && $rating->pivot->queue_length_high) {
                    $payload[$rating->name] = [
                        $rating->pivot->queue_length_low,
                        $rating->pivot->queue_length_high,
                    ];
                }
            }

            return $payload;
        }

        return Rating::query()
            ->join('area_rating', 'ratings.id', '=', 'area_rating.rating_id')
            ->whereNotNull('area_rating.queue_length_low')
            ->whereNotNull('area_rating.queue_length_high')
            ->where('area_rating.queue_length_low', '>', 0)
            ->where('area_rating.queue_length_high', '>', 0)
            ->select('ratings.name', DB::raw('AVG(area_rating.queue_length_low) as avg_low'), DB::raw('AVG(area_rating.queue_length_high) as avg_high'))
            ->groupBy('ratings.name')
            ->get()
            ->keyBy('name')
            ->map(fn ($row) => [$row->avg_low, $row->avg_high])
            ->all();
    }
}
