<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\MapsTrainingObjects;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingReport;
use App\Services\TrainingObjectService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingReportController extends Controller
{
    use MapsTrainingObjects;
    use PaginatesApiResults;
    use ResolvesApiActor;

    public function __construct(private readonly TrainingObjectService $trainingObjectService) {}

    /**
     * List training reports.
     *
     * Includes drafts. Filter with `draft`.
     */
    #[Response(status: 200, description: 'Paginated reports of the training, newest first.')]
    public function index(Request $request, Training $training): JsonResponse
    {
        $filters = $request->validate([
            'draft' => 'sometimes|boolean',
        ]);

        $query = TrainingReport::query()
            ->where('training_id', $training->id)
            ->with(['attachments.file'])
            ->orderByDesc('report_date')
            ->orderByDesc('id');

        if (array_key_exists('draft', $filters)) {
            $query->where('draft', $filters['draft']);
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (TrainingReport $report) => $this->mapReport($report));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Show a training report.
     */
    #[Response(status: 200, description: 'The report with its attachments.')]
    public function show(Training $training, TrainingReport $report): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $report);

        return response()->json(['data' => $this->mapReport($report)], 200);
    }

    /**
     * Create a training report.
     *
     * Only a training in progress can receive a report. Files can be attached as `files[]`.
     * A published report notifies the student when they opted in.
     */
    #[Response(status: 201, description: 'The report was created.')]
    #[Response(status: 409, description: 'A report can only be created for a training in progress.', type: 'array{message: string}')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function store(Request $request, Training $training): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string',
            'contentimprove' => 'sometimes|nullable|string',
            /**
             * The session date.
             *
             * @example 2026-02-15
             */
            'report_date' => 'required|date',
            /**
             * The callsign the session was held on.
             *
             * @example LPPT_TWR
             */
            'position' => 'sometimes|nullable|string|max:255',
            /**
             * Drafts are hidden from the student and do not notify.
             */
            'draft' => 'sometimes|boolean',
            'files' => 'sometimes|array',
            'files.*' => 'file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg|max:10240',
        ]);

        $actor = $this->resolveActor($request);

        if (! $training->status->isInProgress()) {
            return response()->json(['message' => 'A report can only be created for a training in progress.'], 409);
        }

        $report = $this->trainingObjectService->createReport(
            $training,
            [...$data, 'report_date' => Carbon::parse($data['report_date'])],
            $actor?->id,
            $request->file('files', []),
        );

        return response()->json(['data' => $this->mapReport($report->fresh(['attachments.file']))], 201);
    }

    /**
     * Update a training report.
     *
     * Publishing a draft (`draft` false) stamps `published_at` and notifies the student when they opted in.
     */
    #[Response(status: 200, description: 'The report was updated.')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function update(Request $request, Training $training, TrainingReport $report): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $report);

        $data = $request->validate([
            'content' => 'sometimes|required|string',
            'contentimprove' => 'sometimes|nullable|string',
            'report_date' => 'sometimes|required|date',
            'position' => 'sometimes|nullable|string|max:255',
            'draft' => 'sometimes|boolean',
        ]);

        $this->resolveActor($request);

        if (isset($data['report_date'])) {
            $data['report_date'] = Carbon::parse($data['report_date']);
        }

        $report = $this->trainingObjectService->updateReport($report, $data);

        return response()->json(['data' => $this->mapReport($report->fresh(['attachments.file']))], 200);
    }

    /**
     * Delete a training report.
     */
    #[Response(status: 200, description: 'The report was deleted.')]
    public function destroy(Request $request, Training $training, TrainingReport $report): JsonResponse
    {
        $this->ensureBelongsToTraining($training, $report);
        $this->resolveActor($request);

        $mapped = $this->mapReport($report->load('attachments.file'));
        $report->delete();

        return response()->json(['data' => $mapped], 200);
    }

    private function ensureBelongsToTraining(Training $training, TrainingReport $report): void
    {
        abort_unless((int) $report->training_id === (int) $training->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapReport(TrainingReport $report): array
    {
        return [
            'id' => $report->id,
            'training_id' => $report->training_id,
            'written_by_id' => $report->written_by_id,
            'content' => $report->content,
            'contentimprove' => $report->contentimprove,
            'report_date' => $this->iso($report->report_date),
            'position' => $report->position,
            'draft' => (bool) $report->draft,
            'published_at' => $this->iso($report->published_at),
            'created_at' => $this->iso($report->created_at),
            'updated_at' => $this->iso($report->updated_at),
            'attachments' => $this->mapAttachments($report),
        ];
    }
}
