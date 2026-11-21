<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Endorsement;
use App\Models\User;
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

    /**
     * List the ATC roster for an area.
     */
    #[Response(
        status: 200,
        description: 'Active controllers and visiting endorsement holders in the area.',
        type: 'array{data: array{active: array<int, array{id: int, first_name: string, last_name: string, rating: string}>, visiting: array<int, array{id: int, first_name: string, last_name: string, rating: string}>}}',
    )]
    public function roster(Area $area): JsonResponse
    {
        $active = User::allActiveInArea($area)->map(fn (User $user) => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'rating' => $user->rating_short,
        ])->values();

        $visiting = Endorsement::query()
            ->where('type', 'VISITING')
            ->where('expired', false)
            ->where('revoked', false)
            ->whereHas('areas', fn ($q) => $q->where('areas.id', $area->id))
            ->with('user')
            ->get()
            ->map(fn (Endorsement $endorsement) => [
                'id' => $endorsement->user->id,
                'first_name' => $endorsement->user->first_name,
                'last_name' => $endorsement->user->last_name,
                'rating' => $endorsement->user->rating_short,
            ])
            ->unique('id')
            ->values();

        return response()->json([
            'data' => [
                'active' => $active,
                'visiting' => $visiting,
            ],
        ], 200);
    }

    /**
     * List ATC activity rows for an area.
     */
    #[Response(
        status: 200,
        description: 'Per-user ATC activity for the area.',
        type: 'array{data: array<int, array{user_id: int, hours: float|null, hours_in_period: float|null, atc_active: bool, last_online: string|null, start_of_grace_period: string|null}>}',
    )]
    public function activity(Area $area): JsonResponse
    {
        $activity = AtcActivity::query()
            ->where('area_id', $area->id)
            ->orderBy('user_id')
            ->get(['user_id', 'hours', 'hours_in_period', 'atc_active', 'last_online', 'start_of_grace_period']);

        return response()->json(['data' => $activity], 200);
    }
}
