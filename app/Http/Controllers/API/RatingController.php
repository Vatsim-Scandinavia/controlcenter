<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    /**
     * List ratings, including the area pivot fields used for training eligibility.
     */
    #[Response(
        status: 200,
        description: 'Every rating with its area pivots.',
        type: 'array{data: array<int, array{id: int, name: string, vatsim_rating: int|null, areas: array<int, array{id: int, name: string, required_vatsim_rating: int|null, allow_bundling: bool|null, hour_requirement: int|null, queue_length_low: int|null, queue_length_high: int|null}>}>}',
    )]
    public function index(): JsonResponse
    {
        $ratings = Rating::query()
            ->with('areas')
            ->orderBy('id')
            ->get()
            ->map(function (Rating $rating) {
                return [
                    'id' => $rating->id,
                    'name' => $rating->name,
                    'vatsim_rating' => $rating->vatsim_rating?->value,
                    'areas' => $rating->areas->map(fn ($area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'required_vatsim_rating' => $area->pivot->required_vatsim_rating,
                        'allow_bundling' => (bool) $area->pivot->allow_bundling,
                        'hour_requirement' => $area->pivot->hour_requirement,
                        'queue_length_low' => $area->pivot->queue_length_low,
                        'queue_length_high' => $area->pivot->queue_length_high,
                    ])->values(),
                ];
            });

        return response()->json(['data' => $ratings], 200);
    }
}
