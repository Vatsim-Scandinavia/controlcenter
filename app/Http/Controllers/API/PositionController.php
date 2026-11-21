<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * List positions.
     *
     * Returns every position, reduced to the fields a booking client needs to render
     * and match a callsign. Pass `full=1` to include management fields as well.
     */
    #[Response(
        status: 200,
        description: 'All positions, wrapped in a `data` array. Without `full=1` the query selects three columns only (`callsign`, `frequency`, `name`). With `full=1` each row also includes `id`, `fir`, `rating`, `area_id` and `required_facility_rating_id`.',
        type: 'array{data: array<int, array{callsign: string, frequency: string|null, name: string}>}',
    )]
    public function index(Request $request): JsonResponse
    {
        $full = filter_var($request->query('full'), FILTER_VALIDATE_BOOL)
            && ! $request->attributes->get('unauthenticated');

        if ($full) {
            $positions = Position::query()
                ->orderBy('callsign')
                ->get()
                ->map(fn (Position $position) => $this->mapFullPosition($position));

            return response()->json(['data' => $positions], 200);
        }

        $positions = Position::select(['callsign', 'name', 'frequency'])->get();

        return response()->json(['data' => $positions], 200);
    }

    /**
     * Show a position with management fields.
     */
    #[Response(
        status: 200,
        description: 'A single position including management fields.',
        type: 'array{data: array{id: int, callsign: string, name: string, frequency: string|null, fir: string|null, rating: int|null, area_id: int|null, required_facility_rating_id: int|null}}',
    )]
    public function show(Position $position): JsonResponse
    {
        return response()->json(['data' => $this->mapFullPosition($position)], 200);
    }

    /**
     * Create a position.
     */
    #[Response(status: 201, description: 'The position was created.')]
    public function store(PositionRequest $request): JsonResponse
    {
        $position = Position::create($request->validated());

        return response()->json(['data' => $this->mapFullPosition($position)], 201);
    }

    /**
     * Update a position.
     */
    #[Response(status: 200, description: 'The position was updated.')]
    public function update(PositionRequest $request, Position $position): JsonResponse
    {
        $position->update($request->validated());

        return response()->json(['data' => $this->mapFullPosition($position)], 200);
    }

    /**
     * Delete a position.
     */
    #[Response(status: 200, description: 'The position was deleted.')]
    public function destroy(Position $position): JsonResponse
    {
        $position->delete();

        return response()->json(['data' => ['id' => $position->id]], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapFullPosition(Position $position): array
    {
        return [
            'id' => $position->id,
            'callsign' => $position->callsign,
            'name' => $position->name,
            'frequency' => $position->frequency,
            'fir' => $position->fir,
            'rating' => $position->rating?->value,
            'area_id' => $position->area_id,
            'required_facility_rating_id' => $position->required_facility_rating_id,
        ];
    }
}
