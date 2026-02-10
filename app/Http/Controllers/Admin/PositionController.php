<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VatsimRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Area;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request, PositionService $service, $area = null): View
    {
        $allAreas = Area::orderBy('name')->get();

        $accessibleAreas = $allAreas->filter(fn ($a) => $request->user()->can('viewAny', [Position::class, $a]));

        $currentAreaId = $area ?? $request->input('area');
        $currentArea = $currentAreaId ? $allAreas->firstWhere('id', $currentAreaId) : null;

        $this->authorize('viewAny', [Position::class, $currentArea]);

        $positions = $service->getPositions($currentArea, $accessibleAreas);
        $ratings = VatsimRating::getControllerRatings();

        return view('admin.positions.index', [
            'positions' => $positions,
            'ratings' => $ratings,
            'areas' => $accessibleAreas,
            'currentArea' => $currentArea,
        ]);
    }

    public function store(PositionRequest $request)
    {
        $this->authorize('create', new Position($request->all()));

        $position = Position::create($request->validated());

        return redirect()->route('positions.index.area', $position->area_id)->with('success', 'Position ' . $position->callsign . ' created successfully.');
    }

    public function update(PositionRequest $request, Position $position)
    {
        $this->authorize('update', $position);
        $validatedData = $request->validated();
        $position->update($validatedData);

        return redirect()->route('positions.index.area', $position->area_id)->with('success', 'Position ' . $position->callsign . ' updated successfully.');
    }

    public function destroy(Position $position)
    {
        $this->authorize('delete', $position);

        $areaId = $position->area_id;
        $position->delete();

        return redirect()->route('positions.index.area', $areaId)->with('success', 'Position ' . $position->callsign . ' deleted successfully.');
    }
}
