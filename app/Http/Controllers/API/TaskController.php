<?php

namespace App\Http\Controllers\API;

use App\Helpers\TaskStatus;
use App\Http\Controllers\API\Concerns\MapsTrainingObjects;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Services\TaskService;
use App\Tasks\Types\Types;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    use MapsTrainingObjects;
    use PaginatesApiResults;
    use ResolvesApiActor;

    public function __construct(private readonly TaskService $taskService) {}

    /**
     * List tasks.
     */
    #[Response(
        status: 200,
        description: 'Paginated tasks, newest first, filterable by status, type, assignee, creator, subject user, and training.',
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            /**
             * The task status: `0` pending, `1` completed, `-1` declined.
             *
             * @example 0
             */
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'type' => ['sometimes', Rule::in($this->typeNames())],
            'assignee_user_id' => 'sometimes|integer|exists:users,id',
            'creator_user_id' => 'sometimes|integer|exists:users,id',
            'subject_user_id' => 'sometimes|integer|exists:users,id',
            'training_id' => 'sometimes|integer|exists:trainings,id',
        ]);

        $query = Task::query()->with(['subject', 'subjectTraining.ratings', 'subjectTrainingRating'])->orderByDesc('id');

        foreach (['status', 'assignee_user_id', 'creator_user_id', 'subject_user_id'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['training_id'])) {
            $query->where('subject_training_id', $filters['training_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $this->typeClass($filters['type']));
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (Task $task) => $this->mapTask($task));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Create a task.
     *
     * Creates a request for a staff member, such as a rating upgrade, regarding a training.
     * `type` is the task type class name, for example `RatingUpgrade`, `TheoreticalExam`,
     * `SoloEndorsement` or `Custom`. `actor_cid` is recorded as the creator.
     */
    #[Response(status: 201, description: 'The task was created.')]
    #[Response(status: 409, description: 'The assignee is not allowed to receive tasks.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in($this->typeNames())],
            'message' => ['required_if:type,Custom', 'nullable', 'string', 'min:3', 'max:256'],
            'subject_training_id' => 'required|integer|exists:trainings,id',
            /**
             * The student the task concerns. Defaults to the student of the training.
             *
             * @example 10000001
             */
            'subject_user_id' => 'sometimes|integer|exists:users,id',
            'subject_training_rating_id' => 'sometimes|nullable|integer|exists:ratings,id',
            'assignee_user_id' => 'required|integer|exists:users,id',
        ]);

        $actor = $this->resolveActor($request);
        $assignee = User::findOrFail($data['assignee_user_id']);

        if (! $this->taskService->canReceiveTasks($assignee)) {
            return response()->json(['message' => 'Recipient is not allowed to receive tasks.'], 409);
        }

        $task = $this->taskService->createTask([
            'type' => $this->typeClass($data['type']),
            'message' => $data['message'] ?? null,
            'subject_user_id' => $data['subject_user_id'] ?? Training::findOrFail($data['subject_training_id'])->user_id,
            'subject_training_id' => $data['subject_training_id'],
            'subject_training_rating_id' => $data['subject_training_rating_id'] ?? null,
            'assignee_user_id' => $assignee->id,
            'creator_user_id' => $actor?->id,
        ]);

        return response()->json(['data' => $this->mapTask($task->fresh(['subject', 'subjectTraining.ratings', 'subjectTrainingRating']))], 201);
    }

    /**
     * Complete a task.
     *
     * Runs the type specific completion, such as requesting a rating upgrade from the Division API.
     * `actor_cid` is required when the Division API is involved.
     */
    #[Response(status: 200, description: 'The task was completed.')]
    #[Response(status: 409, description: 'The task is not pending.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the request.')]
    public function complete(Request $request, Task $task): JsonResponse
    {
        $actor = $this->resolveTaskActor($request, $task);

        if ($task->status !== TaskStatus::PENDING) {
            return response()->json(['message' => 'The task is not pending.'], 409);
        }

        $error = $this->taskService->completeTask($task, $actor?->id);
        if ($error !== null) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json(['data' => $this->mapTask($task->fresh(['subject', 'subjectTraining.ratings', 'subjectTrainingRating']))], 200);
    }

    /**
     * Decline a task.
     */
    #[Response(status: 200, description: 'The task was declined.')]
    #[Response(status: 409, description: 'The task is not pending.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function decline(Request $request, Task $task): JsonResponse
    {
        $actor = $this->resolveTaskActor($request, $task);

        if ($task->status !== TaskStatus::PENDING) {
            return response()->json(['message' => 'The task is not pending.'], 409);
        }

        $error = $this->taskService->declineTask($task, $actor?->id);
        if ($error !== null) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json(['data' => $this->mapTask($task->fresh(['subject', 'subjectTraining.ratings', 'subjectTrainingRating']))], 200);
    }

    /**
     * Resolve the actor, requiring it for task types that call the Division API.
     */
    private function resolveTaskActor(Request $request, Task $task): ?User
    {
        return $task->type()->isApproval() ? $this->requireActor($request) : $this->resolveActor($request);
    }

    /**
     * The short class names of the task types.
     *
     * @return array<int, string>
     */
    private function typeNames(): array
    {
        return array_map(fn (Types $type) => class_basename($type), $this->taskService->getTypes());
    }

    /**
     * The fully qualified class name of a task type short name.
     */
    private function typeClass(string $name): string
    {
        foreach ($this->taskService->getTypes() as $type) {
            if (class_basename($type) === $name) {
                return $type::class;
            }
        }

        abort(422, 'The selected task type does not exist.');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'type' => class_basename($task->type),
            'type_name' => $task->type()->getName(),
            'text' => $task->type()->getText($task),
            'status' => $task->status->value,
            'status_label' => strtolower($task->status->name),
            'message' => $task->message,
            'subject_user_id' => $task->subject_user_id,
            'subject_training_id' => $task->subject_training_id,
            'subject_training_rating_id' => $task->subject_training_rating_id,
            'assignee_user_id' => $task->assignee_user_id,
            'creator_user_id' => $task->creator_user_id,
            'created_at' => $this->iso($task->created_at),
            'closed_at' => $this->iso($task->closed_at),
        ];
    }
}
