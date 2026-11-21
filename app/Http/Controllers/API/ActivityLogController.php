<?php

namespace App\Http\Controllers\API;

use App\Helpers\ActivityLevel;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    use PaginatesApiResults;

    /**
     * List activity log entries.
     */
    #[Response(status: 200, description: 'Paginated activity log entries.')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'log_name' => 'sometimes|string',
            'level' => ['sometimes', Rule::in(array_map(fn (ActivityLevel $level) => $level->value, ActivityLevel::cases()))],
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
        ]);

        $query = ActivityLog::query()
            ->with('causer')
            ->latest();

        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (ActivityLog $log) => [
            'id' => $log->id,
            'log_name' => $log->log_name,
            'description' => $log->description,
            'level' => $log->level instanceof ActivityLevel ? $log->level->value : $log->level,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'causer_id' => $log->causer_id,
            'created_at' => $log->created_at?->toIso8601String(),
            'properties' => $log->properties,
        ]);

        return response()->json($this->paginatedResponse($paginator), 200);
    }
}
