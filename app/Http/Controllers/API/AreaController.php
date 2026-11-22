<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class AreaController extends Controller
{
    /**
     * List areas.
     */
    #[Response(
        status: 200,
        description: 'Every area, wrapped in a `data` array.',
        type: 'array{data: array<int, array{id: int, name: string, contact: string|null, waiting_time: int|null, feedback_url: string|null, readme_url: string|null}>}',
    )]
    public function index(): JsonResponse
    {
        $areas = Area::query()
            ->orderBy('id')
            ->get(['id', 'name', 'contact', 'waiting_time', 'feedback_url', 'readme_url']);

        return response()->json(['data' => $areas], 200);
    }

    /**
     * Show an area.
     */
    #[Response(
        status: 200,
        description: 'A single area.',
        type: 'array{data: array{id: int, name: string, contact: string|null, waiting_time: int|null, feedback_url: string|null, readme_url: string|null}}',
    )]
    public function show(Area $area): JsonResponse
    {
        return response()->json([
            'data' => $area->only(['id', 'name', 'contact', 'waiting_time', 'feedback_url', 'readme_url']),
        ], 200);
    }
}
