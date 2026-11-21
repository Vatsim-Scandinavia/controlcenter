<?php

namespace App\Http\Controllers\API;

use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Http\Controllers\API\Concerns\MapsTrainingObjects;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Services\TrainingObjectService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingExaminationController extends Controller
{
    use MapsTrainingObjects;
    use PaginatesApiResults;
    use ResolvesApiActor;

    private const RESULTS = ['FAILED', 'PASSED', 'INCOMPLETE', 'POSTPONED'];

    public function __construct(private readonly TrainingObjectService $trainingObjectService) {}

    /**
     * List training examinations.
     */
    #[Response(status: 200, description: 'Paginated examinations of the training, newest first.')]
    public function index(Request $request, Training $training): JsonResponse
    {
        $filters = $request->validate([
            'result' => ['sometimes', Rule::in(self::RESULTS)],
        ]);

        $query = TrainingExamination::query()
            ->where('training_id', $training->id)
            ->with(['position', 'attachments.file'])
            ->orderByDesc('examination_date')
            ->orderByDesc('id');

        if (isset($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (TrainingExamination $examination) => $this->mapExamination($examination));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Show a training examination.
     */
    #[Response(status: 200, description: 'The examination with its attachments.')]
    public function show(Training $training, TrainingExamination $examination): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $examination);

        return response()->json(['data' => $this->mapExamination($examination)], 200);
    }

    /**
     * Create a training examination.
     *
     * Only a training awaiting its exam can receive an examination. `actor_cid` is the examiner,
     * and is required when result files are uploaded for an S2+ examination, because they are
     * forwarded to the Division API. The student and mentors are notified, and a rating upgrade
     * task is created when `request_task_user_id` is given.
     */
    #[Response(status: 201, description: 'The examination was created.')]
    #[Response(status: 409, description: 'An examination can only be created for a training awaiting exam.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the result upload.')]
    public function store(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            /**
             * The callsign of the examined position.
             *
             * @example LPPT_TWR
             */
            'position' => 'required|exists:positions,callsign',
            'result' => ['required', Rule::in(self::RESULTS)],
            /**
             * The examination date. Defaults to today.
             *
             * @example 2026-02-15
             */
            'examination_date' => 'sometimes|date',
            'request_task_user_id' => 'sometimes|nullable|exists:users,id',
            'subject_training_rating_id' => 'sometimes|nullable|exists:ratings,id',
            'files' => 'sometimes|array',
            'files.*' => 'file|mimes:pdf|max:10240',
        ]);

        $files = $request->file('files', []);
        $actor = $this->forwardsToDivisionApi($training, $files) ? $this->requireActor($request) : $this->resolveActor($request);

        if ($training->status !== TrainingStatus::AWAITING_EXAM) {
            return response()->json(['message' => 'An examination can only be created for a training awaiting exam.'], 409);
        }

        $result = $this->trainingObjectService->createExamination(
            $training,
            [...$data, 'examination_date' => isset($data['examination_date']) ? Carbon::parse($data['examination_date']) : now()],
            $actor?->id,
            $files,
        );

        if (is_string($result)) {
            return response()->json(['message' => $result], 422);
        }

        return response()->json(['data' => $this->mapExamination($result->fresh(['position', 'attachments.file']))], 201);
    }

    /**
     * Update a training examination.
     */
    #[Response(status: 200, description: 'The examination was updated.')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function update(Request $request, Training $training, TrainingExamination $examination): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $examination);

        $data = $request->validate([
            'position' => 'sometimes|required|exists:positions,callsign',
            'result' => ['sometimes', 'required', Rule::in(self::RESULTS)],
            'examination_date' => 'sometimes|required|date',
        ]);

        $this->resolveActor($request);

        $changes = [];
        if (isset($data['position'])) {
            $changes['position_id'] = Position::firstWhere('callsign', $data['position'])->id;
        }
        if (isset($data['result'])) {
            $changes['result'] = $data['result'];
        }
        if (isset($data['examination_date'])) {
            $changes['examination_date'] = Carbon::parse($data['examination_date'])->format('Y-m-d');
        }

        $examination = $this->trainingObjectService->updateExamination($examination, $changes);

        return response()->json(['data' => $this->mapExamination($examination->fresh(['position', 'attachments.file']))], 200);
    }

    /**
     * Delete a training examination.
     */
    #[Response(status: 200, description: 'The examination was deleted.')]
    public function destroy(Request $request, Training $training, TrainingExamination $examination): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $examination);
        $this->resolveActor($request);

        $mapped = $this->mapExamination($examination->load(['position', 'attachments.file']));
        $this->trainingObjectService->deleteExamination($examination);

        return response()->json(['data' => $mapped], 200);
    }

    /**
     * Whether the uploaded result files would be forwarded to the Division API.
     *
     * @param  array<int, mixed>  $files
     */
    private function forwardsToDivisionApi(Training $training, array $files): bool
    {
        return $files !== []
            && $training->hasVatsimRatings()
            && $training->getHighestVatsimRating()->vatsim_rating->isGreaterThanOrEqual(VatsimRating::S2);
    }

    private function ensureBelongsToTraining(Training $training, TrainingExamination $examination): void
    {
        abort_unless((int) $examination->training_id === (int) $training->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapExamination(TrainingExamination $examination): array
    {
        return [
            'id' => $examination->id,
            'training_id' => $examination->training_id,
            'position_id' => $examination->position_id,
            'position' => $examination->position?->callsign,
            'examiner_id' => $examination->examiner_id,
            'result' => $examination->result,
            'examination_date' => $this->iso($examination->examination_date),
            'created_at' => $this->iso($examination->created_at),
            'updated_at' => $this->iso($examination->updated_at),
            'attachments' => $this->mapAttachments($examination),
        ];
    }
}
