<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogName;
use App\Helpers\TrainingStatus;
use App\Http\Controllers\API\Concerns\MapsTrainingObjects;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TrainingController as WebTrainingController;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TrainingService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    use MapsTrainingObjects;
    use PaginatesApiResults;
    use ResolvesApiActor;

    public function __construct(private readonly TrainingService $trainingService) {}

    /**
     * List trainings.
     */
    #[Response(
        status: 200,
        description: 'Paginated trainings, newest first, filterable by status, area, user, and type.',
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(TrainingStatus::class)],
            'area_id' => 'sometimes|integer|exists:areas,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'type' => ['sometimes', Rule::in(array_keys(WebTrainingController::$types))],
        ]);

        $query = Training::query()->with(['ratings', 'mentors'])->orderByDesc('id');

        foreach (['status', 'area_id', 'user_id', 'type'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (Training $training) => $this->mapTraining($training));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Show a training.
     */
    #[Response(status: 200, description: 'The training with its ratings and mentors.')]
    public function show(Training $training): JsonResponse
    {
        return response()->json(['data' => $this->mapTraining($training)], 200);
    }

    /**
     * Create a training.
     *
     * Staff creation for a student by `user_id` or `cid`. A student can only have one open
     * training at a time, and refresh or familiarisation trainings must cover every active
     * endorsement of the student in the area.
     */
    #[Response(status: 201, description: 'The training was created.')]
    #[Response(status: 409, description: 'The student already has an open training, or a refresh training does not cover their active endorsements.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            /**
             * The VATSIM CID of the student. Alias of `cid`.
             *
             * @example 10000001
             */
            'user_id' => 'required_without:cid|integer|exists:users,id',
            /**
             * The VATSIM CID of the student. Alias of `user_id`.
             *
             * @example 10000001
             */
            'cid' => 'required_without:user_id|integer|exists:users,id',
            'area_id' => 'required|integer|exists:areas,id',
            /**
             * The training type: `1` Standard, `2` Refresh, `3` Transfer, `4` Fast-track, `5` Familiarisation.
             *
             * @example 1
             */
            'type' => ['required', 'integer', Rule::in(array_keys(WebTrainingController::$types))],
            'rating_ids' => 'required|array|min:1',
            'rating_ids.*' => 'integer|distinct|exists:ratings,id',
            'motivation' => 'sometimes|nullable|string|max:1500',
            'experience' => 'sometimes|nullable|integer|min:1|max:6',
            'english_only' => 'sometimes|boolean',
        ]);

        $actor = $this->resolveActor($request);
        $student = User::findOrFail($data['user_id'] ?? $data['cid']);

        if ($student->hasActiveTrainings(true)) {
            return response()->json(['message' => 'The user already has an active training request.'], 409);
        }

        $ratings = Rating::find($data['rating_ids']);

        $refusal = $this->refreshRefusal((int) $data['type'], (int) $data['area_id'], $student->id, $ratings);
        if ($refusal !== null) {
            return response()->json(['message' => $refusal], 409);
        }

        $training = $this->trainingService->createTraining(
            student: $student,
            areaId: (int) $data['area_id'],
            type: (int) $data['type'],
            ratings: $ratings,
            createdById: $actor?->id,
            motivation: $data['motivation'] ?? '',
            experience: $data['experience'] ?? null,
            englishOnly: (bool) ($data['english_only'] ?? false),
        );

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 201);
    }

    /**
     * Update a training.
     *
     * Every field is optional. `mentors` replaces the whole mentor list (an `expire_at` defaults
     * to 12 months for new mentors), `paused` starts or ends the pause accounting, and
     * `rating_ids` replaces the ratings while keeping the parts already completed. Setting a
     * closed status runs the same close flow as the close endpoint.
     */
    #[Response(status: 200, description: 'The training was updated.')]
    #[Response(status: 409, description: 'The change was refused: reopening over another open training, a mentor who may not mentor in the area, or a refresh training that does not cover the active endorsements.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the completion.')]
    public function update(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            /**
             * The new status: `-2` closed by staff, `-1` completed, `0` in queue, `1` pre-training, `2` active training, `3` awaiting exam.
             *
             * @example 2
             */
            'status' => ['sometimes', 'required', Rule::in($this->staffAssignableStatuses())],
            'closed_reason' => 'sometimes|nullable|string|max:65',
            'mentors' => 'sometimes|array',
            'mentors.*.user_id' => 'required|integer|distinct|exists:users,id',
            'mentors.*.expire_at' => 'sometimes|nullable|date',
            'paused' => 'sometimes|boolean',
            'rating_ids' => 'sometimes|array|min:1',
            'rating_ids.*' => 'integer|distinct|exists:ratings,id',
        ]);

        $status = isset($data['status']) ? TrainingStatus::from((int) $data['status']) : null;
        $actor = $this->resolveActorForCompletion($request, $training, $status === TrainingStatus::COMPLETED);

        $mentors = null;
        if (array_key_exists('mentors', $data)) {
            $mentors = [];
            foreach ($data['mentors'] as $mentor) {
                $mentors[(int) $mentor['user_id']] = isset($mentor['expire_at']) ? Carbon::parse($mentor['expire_at']) : null;
            }

            $unqualified = User::findMany(array_keys($mentors))
                ->reject(fn (User $user) => $user->hasPermission('training.mentor', $training->area));
            if ($unqualified->isNotEmpty()) {
                return response()->json(['message' => 'User ' . $unqualified->pluck('id')->implode(', ') . ' cannot be assigned as a mentor in this area.'], 409);
            }
        }

        if (isset($data['rating_ids'])) {
            $ratings = Rating::find($data['rating_ids']);

            $refusal = $this->refreshRefusal((int) $training->type, (int) $training->area_id, $training->user_id, $ratings);
            if ($refusal !== null) {
                return response()->json(['message' => $refusal], 409);
            }

            $previousRatings = $training->ratings;
            $this->trainingService->replaceRatings($training, $ratings);
            $training->load('ratings');

            ActivityLogService::warning(LogName::Training, 'Updated training request ' . $training->id . ' via API' .
                ' ― Old Ratings: ' . $previousRatings->pluck('name') .
                ' ― New Ratings: ' . $ratings->pluck('name'));
        }

        $changes = ['status' => $status, 'mentors' => $mentors];
        if (array_key_exists('closed_reason', $data)) {
            $changes['closed_reason'] = $data['closed_reason'];
        }
        if (array_key_exists('paused', $data)) {
            $changes['paused'] = (bool) $data['paused'];
        }

        $result = $this->trainingService->updateDetails($training, $changes, $actor?->id);

        if ($result['refused']) {
            return response()->json(['message' => $result['error']], 409);
        }

        if ($result['error'] !== null) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 200);
    }

    /**
     * Close a training.
     *
     * Closes an open training as closed by staff (default) or completed, detaching the
     * mentors and notifying the student with the optional reason.
     */
    #[Response(status: 200, description: 'The training was closed.')]
    #[Response(status: 409, description: 'The training is already closed.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the completion.')]
    public function close(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            /**
             * The closed status: `-2` closed by staff (default) or `-1` completed.
             *
             * @example -2
             */
            'status' => ['sometimes', Rule::in([TrainingStatus::CLOSED_BY_STAFF->value, TrainingStatus::COMPLETED->value])],
            /**
             * Shown to the student in the closure e-mail.
             *
             * @example Student has not been active.
             */
            'reason' => 'sometimes|nullable|string|max:65',
        ]);

        if ($training->status->isClosed()) {
            return response()->json(['message' => 'The training is already closed.'], 409);
        }

        $status = TrainingStatus::from((int) ($data['status'] ?? TrainingStatus::CLOSED_BY_STAFF->value));
        $actor = $this->resolveActorForCompletion($request, $training, $status === TrainingStatus::COMPLETED);

        $result = $this->trainingService->updateDetails($training, [
            'status' => $status,
            'closed_reason' => $data['reason'] ?? null,
        ], $actor?->id);

        if ($result['error'] !== null) {
            return response()->json(['message' => $result['error']], $result['refused'] ? 409 : 422);
        }

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 200);
    }

    /**
     * Complete a training.
     *
     * Completes the whole training, granting the endorsement of every facility rating
     * through the Division API, and closes it.
     */
    #[Response(status: 200, description: 'The training was completed and closed.')]
    #[Response(status: 409, description: 'Only a training in progress can be completed.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the endorsement.')]
    public function complete(Request $request, Training $training): JsonResponse
    {
        $actor = $this->resolveActorForCompletion($request, $training, true);

        $wasInProgress = $training->status->isInProgress();
        $error = $this->trainingService->completeWholeTraining($training, $actor?->id);

        if ($error !== null) {
            return $this->completionFailure($training, $wasInProgress, $error);
        }

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 200);
    }

    /**
     * Complete one rating of a training.
     *
     * Signs off a single VATSIM rating part of a multi-rating training. Signing off the last
     * outstanding part completes and closes the training.
     */
    #[Response(status: 200, description: 'The rating part was completed.')]
    #[Response(status: 409, description: 'The part cannot be completed: the training is not in progress, has a single rating, does not include the rating, the rating is not a VATSIM rating, or the part is already completed.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the completion.')]
    public function completeRating(Request $request, Training $training, Rating $rating): JsonResponse
    {
        $actor = $this->resolveActorForCompletion($request, $training, true);

        $wasInProgress = $training->status->isInProgress();
        $error = $this->trainingService->completeRatingPart($training, $rating, $actor?->id);

        if ($error !== null) {
            return $this->completionFailure($training, $wasInProgress, $error);
        }

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 200);
    }

    /**
     * Toggle pre-training completion.
     *
     * Flips the pre-training completed flag, or sets it when `completed` is given.
     */
    #[Response(status: 200, description: 'The pre-training flag was updated.')]
    #[Response(status: 409, description: 'The training is not in pre-training.', type: 'array{message: string}')]
    public function preTraining(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            'completed' => 'sometimes|boolean',
        ]);

        $actor = $this->resolveActor($request);

        if ($training->status !== TrainingStatus::PRE_TRAINING) {
            return response()->json(['message' => 'The training is not in pre-training.'], 409);
        }

        $this->trainingService->togglePreTrainingCompleted($training, $actor?->id, array_key_exists('completed', $data) ? (bool) $data['completed'] : null);

        return response()->json(['data' => $this->mapTraining($training->fresh(['ratings', 'mentors']))], 200);
    }

    /**
     * List training activities.
     *
     * The training timeline, newest first.
     */
    #[Response(status: 200, description: 'The timeline entries of the training.')]
    public function activities(Training $training): JsonResponse
    {
        $activities = $training->activities()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TrainingActivity $activity) => $this->mapActivity($activity))
            ->values();

        return response()->json(['data' => $activities], 200);
    }

    /**
     * Add a comment.
     *
     * Adds a staff comment to the training timeline, attributed to `actor_cid` when given.
     */
    #[Response(status: 201, description: 'The comment was added.')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function comment(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            'comment' => 'required|string|max:512',
        ]);

        $actor = $this->resolveActor($request);

        $activity = $this->trainingService->addComment($training, $data['comment'], $actor?->id);

        return response()->json(['data' => $this->mapActivity($activity)], 201);
    }

    /**
     * Resolve the actor, requiring it when the Division API may be called.
     *
     * Completing a training grants the endorsement of each facility rating through the
     * Division API, which needs the requesting CID.
     */
    private function resolveActorForCompletion(Request $request, Training $training, bool $completes): ?User
    {
        $callsDivisionApi = $completes && $training->ratings->whereNull('vatsim_rating')->isNotEmpty();

        return $callsDivisionApi ? $this->requireActor($request) : $this->resolveActor($request);
    }

    /**
     * Map a refused completion: a refusal before the status moved is a conflict, a failure
     * after the training was marked completed comes from the Division API.
     */
    private function completionFailure(Training $training, bool $wasInProgress, string $error): JsonResponse
    {
        $divisionFailed = $wasInProgress && $training->fresh()->status === TrainingStatus::COMPLETED;

        return response()->json(['message' => $error], $divisionFailed ? 422 : 409);
    }

    /**
     * The refusal message when a refresh or familiarisation training would skip active endorsements.
     *
     * @param  Collection<int, Rating>  $ratings
     */
    private function refreshRefusal(int $type, int $areaId, int $userId, Collection $ratings): ?string
    {
        if ($type !== 2 && $type !== 5) {
            return null;
        }

        $missing = $this->trainingService->missingRefreshRatings($areaId, $userId, $ratings->pluck('name'));

        if ($missing->isEmpty()) {
            return null;
        }

        return 'A refresh/familiarisation training requires the student to refresh all of their active endorsements. Add these to the application and try again: ' . $missing->implode(', ');
    }

    /**
     * The statuses staff may set.
     *
     * @return array<int, int>
     */
    private function staffAssignableStatuses(): array
    {
        return collect(TrainingStatus::cases())
            ->filter(fn (TrainingStatus $status) => $status->isAssignableByStaff())
            ->map(fn (TrainingStatus $status) => $status->value)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTraining(Training $training): array
    {
        return [
            'id' => $training->id,
            'user_id' => $training->user_id,
            'area_id' => $training->area_id,
            'type' => (int) $training->type,
            'type_label' => WebTrainingController::$types[$training->type]['text'] ?? null,
            'status' => $training->status->value,
            'status_label' => $training->status->label(),
            'motivation' => $training->motivation,
            'experience' => $training->experience,
            'english_only_training' => (bool) $training->english_only_training,
            'paused' => $training->paused_at !== null,
            'paused_at' => $this->iso($training->paused_at),
            'paused_length' => (int) $training->paused_length,
            'pre_training_completed' => (bool) $training->pre_training_completed,
            'closed_reason' => $training->closed_reason,
            'created_by' => $training->created_by,
            'created_at' => $this->iso($training->created_at),
            'started_at' => $this->iso($training->started_at),
            'closed_at' => $this->iso($training->closed_at),
            'ratings' => $training->ratings->map(fn (Rating $rating) => [
                'id' => $rating->id,
                'name' => $rating->name,
                'completed_at' => $this->iso($rating->pivot->completed_at),
            ])->values(),
            'mentors' => $training->mentors->map(fn (User $mentor) => [
                'user_id' => $mentor->id,
                'name' => $mentor->name,
                'expire_at' => $this->iso($mentor->pivot->expire_at),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapActivity(TrainingActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'training_id' => $activity->training_id,
            'type' => $activity->type,
            'old_data' => $activity->old_data,
            'new_data' => $activity->new_data,
            'comment' => $activity->comment,
            'triggered_by_id' => $activity->triggered_by_id,
            'created_at' => $this->iso($activity->created_at),
        ];
    }
}
