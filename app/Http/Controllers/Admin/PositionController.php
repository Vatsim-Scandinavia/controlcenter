<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VatsimRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Area;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

        return $this->redirectAfterMutation($request, $position->area_id)
            ->with('success', 'Position ' . $position->callsign . ' created successfully.');
    }

    public function update(PositionRequest $request, Position $position)
    {
        $this->authorize('update', $position);

        $validated = $request->validated();

        if ($validated['area_id'] !== $position->area_id) {
            $this->authorize('create', new Position(['area_id' => $validated['area_id']]));
        }

        $position->update($validated);

        return $this->redirectAfterMutation($request, $position->area_id)
            ->with('success', 'Position ' . $position->callsign . ' updated successfully.');
    }

    public function destroy(Request $request, Position $position)
    {
        $this->authorize('delete', $position);

        $areaId = $position->area_id;
        $position->delete();

        return $this->redirectAfterMutation($request, $areaId)
            ->with('success', 'Position ' . $position->callsign . ' deleted successfully.');
    }

    private function redirectAfterMutation(Request $request, int $areaId): RedirectResponse
    {
        $route = $request->user()->hasRole('admin')
            ? route('positions.index.area', $areaId)
            : route('positions.index');

        return redirect($route);
    }
}
