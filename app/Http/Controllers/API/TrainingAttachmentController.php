<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\MapsTrainingObjects;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingObject;
use App\Models\TrainingObjectAttachment;
use App\Models\TrainingReport;
use App\Services\TrainingObjectService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TrainingAttachmentController extends Controller
{
    use MapsTrainingObjects;
    use ResolvesApiActor;

    public function __construct(private readonly TrainingObjectService $trainingObjectService) {}

    /**
     * Upload report attachments.
     *
     * Send one file as `file` or several as `files[]`. `actor_cid` is recorded as the uploader.
     */
    #[Response(status: 201, description: 'The files were attached. Returns every attachment of the report.')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function storeForReport(Request $request, Training $training, TrainingReport $report): JsonResponse
    {
        abort_unless((int) $report->training_id === (int) $training->id, 404);

        return $this->store($request, $report);
    }

    /**
     * Upload examination attachments.
     *
     * Send one file as `file` or several as `files[]`. `actor_cid` is recorded as the uploader.
     */
    #[Response(status: 201, description: 'The files were attached. Returns every attachment of the examination.')]
    #[Response(status: 422, description: 'Validation failed.')]
    public function storeForExamination(Request $request, Training $training, TrainingExamination $examination): JsonResponse
    {
        abort_unless((int) $examination->training_id === (int) $training->id, 404);

        return $this->store($request, $examination);
    }

    /**
     * Download an attachment.
     *
     * Streams the stored file.
     */
    #[Response(status: 200, description: 'The file contents.', type: 'string')]
    #[Response(status: 404, description: 'The attachment or its file does not exist.', type: 'array{message: string}')]
    public function show(TrainingObjectAttachment $attachment): HttpResponse
    {
        $file = $attachment->file;

        if ($file === null || ! Storage::exists($file->full_path)) {
            return response()->json(['message' => 'The attachment file could not be found.'], 404);
        }

        return response(Storage::get($file->full_path), 200)
            ->header('Content-Type', Storage::mimeType($file->full_path))
            ->header('Content-Disposition', 'inline');
    }

    /**
     * Delete an attachment.
     */
    #[Response(status: 200, description: 'The attachment was deleted.')]
    public function destroy(Request $request, TrainingObjectAttachment $attachment): JsonResponse
    {
        $this->resolveActor($request);

        $mapped = [
            'id' => $attachment->id,
            'name' => $attachment->file?->name,
            'hidden' => (bool) $attachment->hidden,
        ];

        $this->trainingObjectService->deleteAttachment($attachment);

        return response()->json(['data' => $mapped], 200);
    }

    private function store(Request $request, TrainingObject $object): JsonResponse
    {
        $request->validate([
            'file' => 'required_without:files|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg|max:10240',
            'files' => 'required_without:file|array|min:1',
            'files.*' => 'file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg|max:10240',
        ]);

        $actor = $this->resolveActor($request);

        $files = [...Arr::wrap($request->file('file', [])), ...$request->file('files', [])];

        $this->trainingObjectService->saveAttachments($files, $object, $actor?->id);

        return response()->json(['data' => $this->mapAttachments($object->fresh('attachments.file'))], 201);
    }
}
