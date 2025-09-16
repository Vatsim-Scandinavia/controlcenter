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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{


    public function index(PositionService $positionService): View
    {
        $this->authorize('viewAny', Position::class);

        $positions = $positionService->getPositions();
        $ratings = collect(VatsimRating::cases())->filter(function ($rating) {
            return !in_array($rating, VatsimRating::NOT_POSITION_RATINGS);
        });
        $canCreate = Auth::user()->groups()->where('id', '<=', 2)->exists();

        return view('admin.positions.index', compact('positions', 'ratings', 'canCreate'));
    }

    public function store(PositionRequest $request)
    {
        $this->authorize('create', new Position($request->all()));

        $position = Position::create($request->validated());

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' created successfully.');
    }

    public function update(PositionRequest $request, Position $position)
    {
        $tempPosition = new Position($request->all());
        $tempPosition->area = Area::findOrFail($request->area_id);
        $this->authorize('update', $tempPosition);

        $position->update($request->validated());

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' updated successfully.');
    }

    public function destroy(Position $position)
    {
        $this->authorize('delete', $position);

        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' deleted successfully.');
    }
}
