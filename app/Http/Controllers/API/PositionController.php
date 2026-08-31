<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Dedoc\Scramble\Attributes\Response;

class PositionController extends Controller
{
    /**
     * List positions.
     *
     * Returns every position, reduced to the fields a booking client needs to render
     * and match a callsign.
     */
    #[Response(
        status: 200,
        description: 'All positions, wrapped in a `data` array. The query selects three columns only, so the response carries no `id`, `fir`, `rating`, `area_id` or `required_facility_rating_id`.',
        type: 'array{data: array<int, array{callsign: string, frequency: string|null, name: string}>}',
    )]
    public function index()
    {
        $positions = Position::select(['callsign', 'name', 'frequency'])->get();

        return response()->json(['data' => $positions], 200);
    }
}
