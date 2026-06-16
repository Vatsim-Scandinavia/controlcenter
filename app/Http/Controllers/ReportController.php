<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\ManagementReport;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Services\Sql\Sql;
use App\Traits\ResolvesAreaScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * This controller handles the report views and statistics
 */
class ReportController extends Controller
{
    use ResolvesAreaScope;

    /**
     * Show the training statistics view
     *
     * @throws AuthorizationException
     */
    public function access(): View
    {
        $this->authorize('viewAccessReport', ManagementReport::class);

        $users = User::has('roleAssignments')->get();

        $areas = Area::all();

        return view('reports.access', compact('users', 'areas'));
    }

    /**
     * Show the training statistics view
     *
     * @param  false|int  $filterArea  areaId to filter by
     *
     * @throws AuthorizationException
     */
    public function trainings(false|int $filterArea = false): View|Response|RedirectResponse
    {
        if (! $filterArea) {
            if ($response = $this->resolveAreaScope(
                'training.statistics.view',
                'reports.training.area',
                'Training Statistics',
            )) {
                return $response;
            }
        }

        if ($filterArea !== false && ! Area::where('id', $filterArea)->exists()) {
            abort(404);
        }

        $this->authorize('accessTrainingReports', [ManagementReport::class, $filterArea]);

        $validated = request()->validate([
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) {
                    $startDate = request()->input('start_date');
                    if (! $startDate || ! $value) {
                        return;
                    }

                    try {
                        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                        $end = Carbon::createFromFormat('Y-m-d', $value)->endOfDay();
                    } catch (\Throwable) {
                        return;
                    }

                    if ($start->diffInDays($end) > 731) {
                        $fail('The selected date range may not exceed 24 months.');
                    }
                },
            ],
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : null;
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : null;

        $areas = Area::all();
        $filterName = 'All Areas';
        if ($filterArea) {
            $area = $areas->find($filterArea);
            if ($area) {
                $filterName = $area->name;
            }
        }

        [$newRequests, $completedRequests, $closedRequests, $passedExamRequests, $failedExamRequests, $labels] = $this->getBiAnnualRequestsStats($filterArea, $startDate, $endDate);
        $cardStats = $this->getCardStats($filterArea, $startDate, $endDate);
        $totalRequests = $this->getDailyRequestsStats($filterArea, $startDate, $endDate);
        $queues = $this->getQueueStats($filterArea);
        $sessionsPerRating = $this->getSessionsPerRatingStats($filterArea, $startDate, $endDate);

        return view('reports.trainings', [
            'filterName' => $filterName,
            'areas' => $areas,
            'cardStats' => $cardStats,
            'totalRequests' => $totalRequests,
            'newRequests' => $newRequests,
            'completedRequests' => $completedRequests,
            'closedRequests' => $closedRequests,
            'passedExamRequests' => $passedExamRequests,
            'failedExamRequests' => $failedExamRequests,
            'queues' => $queues,
            'sessionsPerRating' => $sessionsPerRating,
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
     * @throws AuthorizationException
     */
    public function activities($filterArea = false): View|Response|RedirectResponse
    {
        if (! $filterArea) {
            if ($response = $this->resolveAreaScope(
                'training.activities.view',
                'reports.activities.area',
                'Training Activities',
            )) {
                return $response;
            }
        }

        if ($filterArea !== false && ! Area::where('id', $filterArea)->exists()) {
            abort(404);
        }

        $this->authorize('accessActivityReports', [ManagementReport::class, $filterArea]);

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
                ->where('published_at', '>=', $activities->last()->created_at)
                ->get();

            $examinationReports = TrainingExamination::where('created_at', '>=', $activities->last()->created_at)
                ->when($filterArea, function (Builder $query, $filterArea) {
                    $query->whereHas('training', fn (Builder $q) => $q->where('area_id', $filterArea));
                })
                ->get();

            $entries = $entries->concat($trainingReports)->concat($examinationReports);
        }

        $entries = $entries->concat($activities)->sortByDesc('activity_date');
        $statuses = TrainingController::$statuses;

        $areas = Area::all();
        $filterName = 'All Areas';
        if ($filterArea) {
            $area = $areas->find($filterArea);
            if ($area) {
                $filterName = $area->name;
            }
        }

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
     * @throws AuthorizationException
     */
    public function mentors()
    {
        $this->authorize('viewMentors', ManagementReport::class);

        $scope = auth()->user()->accessibleAreasForPermission('fir.management.reports.view');

        $query = User::whereHas('roleAssignments', function ($q) {
            $q->where('role', 'mentor');
        })->with('trainingReports', 'teaches', 'teaches.reports', 'teaches.user');

        if (! $scope->isGlobal) {
            $query->whereHas('roleAssignments', function (Builder $areaQuery) use ($scope) {
                $areaQuery->whereIn('area_id', $scope->areas->pluck('id'));
            });
        }

        $mentors = $query->get();

        $mentors = $mentors->sortBy('name')->unique();
        $statuses = TrainingController::$statuses;

        return view('reports.mentors', compact('mentors', 'statuses'));
    }

    /**
     * Index received feedback
     *
     * @todo Convert or consider moving out of the ReportController, with slightly
     *       cleaner separation of concerns.
     * @todo Simplify & scope the permission names.
     * @todo Attach controllers (reference users) to a set of areas based on their
     *       activity, so that position-less feedback can be correlated to an area
     *       via the controller for whom the feedback applies.
     */
    public function feedback(): View
    {
        $this->authorize('viewFeedback', ManagementReport::class);

        $user = auth()->user();
        $correlatedScope = $user->accessibleAreasForPermission('feedback.correlated.view');
        $canViewUncorrelated = $user->accessibleAreasForPermission('feedback.uncorrelated.view')->hasAccess();

        $feedback = Feedback::with(['submitter', 'referenceUser', 'referencePosition.area'])
            ->latest()
            ->where(function ($q) use ($correlatedScope, $canViewUncorrelated) {
                if ($correlatedScope->isGlobal) {
                    $q->whereNotNull('reference_position_id');
                } else {
                    $q->whereHas('referencePosition', fn ($q) => $q->whereIn('area_id', $correlatedScope->areas->pluck('id')));
                }

                if ($canViewUncorrelated) {
                    $q->orWhereNull('reference_position_id');
                }
            })
            ->get();

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
        $start = $startDate ?? Carbon::now()->subYear(1)->startOfDay();
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
        $queryStart = $startDate ?? now()->subMonths(6)->startOfMonth();
        $queryEnd = $endDate ?? now()->endOfDay();
        $bucketStart = $queryStart->copy()->startOfMonth();
        $bucketEnd = $queryEnd->copy()->endOfMonth();

        // Initialize translator and labels
        $monthTranslator = [];
        $labels = [];
        $index = 0;
        // Group by month
        $period = CarbonPeriod::create($bucketStart, '1 month', $bucketEnd);

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
            ->where(function ($query) use ($queryStart, $queryEnd) {
                $query->whereBetween('created_at', [$queryStart, $queryEnd])
                    ->orWhere(function ($subQuery) use ($queryStart, $queryEnd) {
                        $subQuery->whereIn('status', [-1, -2])
                            ->whereBetween('closed_at', [$queryStart, $queryEnd]);
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
                if ($training->created_at >= $queryStart && $training->created_at <= $queryEnd) {
                    $key = $training->created_at->format('Y-m');
                    if (isset($monthTranslator[$key])) {
                        $newRequests[$rating->name][$monthTranslator[$key]]++;
                    }
                }

                // Completed and closed requests
                if ($training->closed_at && $training->closed_at >= $queryStart && $training->closed_at <= $queryEnd) {
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
        $passedExamRequests = [];
        $failedExamRequests = [];
        foreach ($ratings as $rating) {
            $passedExamRequests[$rating->name] = array_fill(0, $totalBuckets, 0);
            $failedExamRequests[$rating->name] = array_fill(0, $totalBuckets, 0);
        }

        $examinationsQuery = DB::table('training_examinations')
            ->select(
                DB::raw('count(training_examinations.id) as count'),
                DB::raw(Sql::date('training_examinations.examination_date', 'exam_date')),
                'result',
                'ratings.name as rating_name'
            )
            ->join('trainings', 'trainings.id', '=', 'training_examinations.training_id')
            ->join('rating_training', 'rating_training.training_id', '=', 'trainings.id')
            ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
            ->where(function ($ratingQuery) {
                $ratingQuery->where('ratings.vatsim_rating', '>=', 3)
                    ->whereNotNull('ratings.vatsim_rating')
                    ->orWhereNotNull('ratings.endorsement_type');
            })
            ->whereIn('trainings.type', [1, 4, 5])
            ->whereIn('result', ['PASSED', 'FAILED'])
            ->whereBetween('examination_date', [$queryStart, $queryEnd])
            ->groupBy('exam_date', 'result', 'ratings.name');

        if ($areaFilter) {
            $examinationsQuery->where('trainings.area_id', $areaFilter);
        }

        $examinations = $examinationsQuery->get();

        foreach ($examinations as $entry) {
            $date = Carbon::parse($entry->exam_date);
            $key = $date->format('Y-m');

            if (! isset($monthTranslator[$key])) {
                continue;
            }

            if ($entry->result === 'PASSED' && isset($passedExamRequests[$entry->rating_name])) {
                $passedExamRequests[$entry->rating_name][$monthTranslator[$key]] += $entry->count;
            }

            if ($entry->result === 'FAILED' && isset($failedExamRequests[$entry->rating_name])) {
                $failedExamRequests[$entry->rating_name][$monthTranslator[$key]] += $entry->count;
            }
        }

        // Remove series that have all 0 values across all charts.
        $nonZeroSeriesNames = collect($newRequests)->keys()->filter(
            fn ($series) => array_sum($newRequests[$series]) > 0
                || array_sum($completedRequests[$series]) > 0
                || array_sum($closedRequests[$series]) > 0
        );

        $nonZeroExamSeriesNames = collect($passedExamRequests)->keys()->filter(
            fn ($series) => array_sum($passedExamRequests[$series]) > 0
                || array_sum($failedExamRequests[$series]) > 0
        );

        $filter = fn ($chart) => collect($chart)->only($nonZeroSeriesNames)->all();
        $filterExams = fn ($chart) => collect($chart)->only($nonZeroExamSeriesNames)->all();

        return [
            $filter($newRequests),
            $filter($completedRequests),
            $filter($closedRequests),
            $filterExams($passedExamRequests),
            $filterExams($failedExamRequests),
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

    /**
     * Return per-rating training session statistics.
     *
     * A session is a single non-draft training report. Volume is the count of
     * non-draft reports within the window/area, attributed to each rating linked
     * to the report's training. Average/median are computed over the per-training
     * session counts of ended (completed/closed) trainings within the window/area
     * that recorded at least one session; trainings with zero sessions (e.g. queue
     * drop-outs) are excluded. When a rating has no qualifying sample, average and
     * median are null so the chart omits the marker rather than plotting a false 0.
     *
     * @param  false|int  $areaFilter  areaId to filter by
     * @param  ?Carbon  $startDate  window start; defaults to 6 months ago
     * @param  ?Carbon  $endDate  window end; defaults to now
     * @return array<string, array{volume: int, average: ?float, median: ?float}>
     */
    protected function getSessionsPerRatingStats(false|int $areaFilter, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $queryStart = $startDate ?? now()->subMonths(6)->startOfMonth();
        $queryEnd = $endDate ?? now()->endOfDay();

        // Volume: count of non-draft reports per rating, scoped by activity date.
        $activityDate = Sql::coalesce('training_reports.published_at', 'training_reports.created_at');

        $volumeQuery = DB::table('training_reports')
            ->select('ratings.id as rating_id', 'ratings.name as rating_name', DB::raw('count(training_reports.id) as volume'))
            ->join('trainings', 'trainings.id', '=', 'training_reports.training_id')
            ->join('rating_training', 'rating_training.training_id', '=', 'trainings.id')
            ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
            ->where('training_reports.draft', false)
            ->whereRaw("$activityDate >= ?", [$queryStart])
            ->whereRaw("$activityDate <= ?", [$queryEnd])
            ->groupBy('ratings.id', 'ratings.name');

        if ($areaFilter) {
            $volumeQuery->where('trainings.area_id', $areaFilter);
        }

        // Accumulate per-rating data keyed by rating id, so the final order can
        // follow the ratings table (training progression) like the sibling charts.
        $ratings = [];
        foreach ($volumeQuery->get() as $row) {
            $ratings[$row->rating_id]['name'] = $row->rating_name;
            $ratings[$row->rating_id]['volume'] = (int) $row->volume;
        }

        // Average/median: per-training non-draft report counts over ended trainings.
        $sampleQuery = DB::table('trainings')
            ->select(
                'ratings.id as rating_id',
                'ratings.name as rating_name',
                DB::raw('count(training_reports.id) as report_count')
            )
            ->join('rating_training', 'rating_training.training_id', '=', 'trainings.id')
            ->join('ratings', 'ratings.id', '=', 'rating_training.rating_id')
            // Inner join: trainings with zero non-draft sessions are excluded from
            // the sample. A closed training that never recorded a session is a queue
            // drop-out, not a training that "took zero sessions", so counting it
            // would wrongly drag the average/median toward zero.
            ->join('training_reports', function ($join) {
                $join->on('training_reports.training_id', '=', 'trainings.id')
                    ->where('training_reports.draft', '=', false);
            })
            ->whereIn('trainings.status', [-1, -2])
            ->whereBetween('trainings.closed_at', [$queryStart, $queryEnd])
            ->groupBy('trainings.id', 'ratings.id', 'ratings.name');

        if ($areaFilter) {
            $sampleQuery->where('trainings.area_id', $areaFilter);
        }

        foreach ($sampleQuery->get() as $row) {
            $ratings[$row->rating_id]['name'] = $row->rating_name;
            $ratings[$row->rating_id]['sample'][] = (int) $row->report_count;
        }

        // Order by rating id (S1, S2, S3, …) to match the sibling charts' x-axis.
        ksort($ratings);

        $payload = [];
        foreach ($ratings as $rating) {
            $volume = $rating['volume'] ?? 0;
            $sample = $rating['sample'] ?? [];

            // Drop ratings with no volume and no ended-training sample.
            if ($volume === 0 && count($sample) === 0) {
                continue;
            }

            $payload[$rating['name']] = [
                'volume' => $volume,
                'average' => $this->mean($sample),
                'median' => $this->median($sample),
            ];
        }

        return $payload;
    }

    /**
     * Compute the mean of a sample, rounded to one decimal. Returns null for an
     * empty sample so callers can omit the data point rather than plot a false 0.
     *
     * @param  array<int>  $sample
     */
    private function mean(array $sample): ?float
    {
        if (count($sample) === 0) {
            return null;
        }

        return round(array_sum($sample) / count($sample), 1);
    }

    /**
     * Compute the median of a sample. Even-sized samples return the mean of the
     * two middle values. Returns null for an empty sample so callers can omit the
     * data point rather than plot a false 0.
     *
     * @param  array<int>  $sample
     */
    private function median(array $sample): ?float
    {
        $count = count($sample);
        if ($count === 0) {
            return null;
        }

        sort($sample);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round(($sample[$middle - 1] + $sample[$middle]) / 2, 1);
        }

        return round((float) $sample[$middle], 1);
    }
}
