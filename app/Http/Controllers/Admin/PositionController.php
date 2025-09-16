<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VatsimRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Area;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    public function index(PositionService $positionService): View
    {
        $this->authorize('viewAny', Position::class);

        $positions = $positionService->getPositions();
        $ratings = VatsimRating::getControllerRatings();
        $areas = Area::orderBy('name')->get()->filter(function ($area) {
            return Auth::user()->isModeratorOrAbove($area);
        });

        return view('admin.positions.index', compact('positions', 'ratings', 'areas'));
    }

    public function store(PositionRequest $request)
    {
        $this->authorize('create', new Position($request->all()));

        $position = Position::create($request->validated());

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' created successfully.');
    }

    public function update(PositionRequest $request, Position $position)
    {
        $this->authorize('update', $position);
        $validatedData = $request->validated();
        $position->update($validatedData);

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' updated successfully.');
    }

    public function destroy(Position $position)
    {
        $this->authorize('delete', $position);

        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' deleted successfully.');
    }
}
