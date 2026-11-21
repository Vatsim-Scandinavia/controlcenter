<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Position;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    use PaginatesApiResults;

    /**
     * List feedback submissions.
     */
    #[Response(status: 200, description: 'Paginated feedback rows.')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'area_id' => 'sometimes|integer|exists:areas,id',
            'forwarded' => 'sometimes|boolean',
        ]);

        $query = Feedback::query()
            ->with(['submitter', 'referenceUser', 'referencePosition', 'explicitArea'])
            ->orderByDesc('id');

        if (isset($filters['area_id'])) {
            $query->inArea([$filters['area_id']]);
        }

        if (array_key_exists('forwarded', $filters)) {
            $query->where('forwarded', $filters['forwarded']);
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (Feedback $feedback) => $this->mapFeedback($feedback));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Update the controller, position, and area references on a feedback row.
     */
    #[Response(status: 200, description: 'The feedback references were updated.')]
    public function update(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'position' => 'nullable|exists:positions,callsign',
            'controller' => 'nullable|integer|exists:users,id',
            'area' => [
                'nullable',
                'exists:areas,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (filled($value) && $request->filled('position')) {
                        $fail('An explicit area cannot be set when a position is selected.');
                    }
                },
            ],
        ]);

        $feedback->reference_user_id = $data['controller'] ?? null;
        $feedback->reference_position_id = isset($data['position'])
            ? Position::where('callsign', $data['position'])->value('id')
            : null;
        $feedback->area_id = $data['area'] ?? null;
        $feedback->save();

        return response()->json([
            'data' => $this->mapFeedback($feedback->fresh(['submitter', 'referenceUser', 'referencePosition', 'explicitArea'])),
        ], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapFeedback(Feedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'feedback' => $feedback->feedback,
            'submitter_user_id' => $feedback->submitter_user_id,
            'reference_user_id' => $feedback->reference_user_id,
            'reference_position_id' => $feedback->reference_position_id,
            'area_id' => $feedback->area_id,
            'forwarded' => (bool) $feedback->forwarded,
            'created_at' => $feedback->created_at?->toIso8601String(),
            'updated_at' => $feedback->updated_at?->toIso8601String(),
        ];
    }
}
