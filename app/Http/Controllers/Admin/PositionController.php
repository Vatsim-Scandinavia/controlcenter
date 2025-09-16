<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VatsimRating;
use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::user()->isModeratorOrAbove()) {
                return $next($request);
            }

            abort(403);
        });
    }

    public function index(): View
    {
        $positions = Position::with('area')->get();
        $ratings = collect(VatsimRating::cases())->filter(function ($rating) {
            return !in_array($rating, [VatsimRating::INA, VatsimRating::SUS, VatsimRating::OBS, VatsimRating::SUP, VatsimRating::ADM]);
        });

        return view('admin.positions.index', compact('positions', 'ratings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'callsign' => 'required|unique:positions,callsign',
            'name' => 'required',
            'frequency' => 'required',
            'fir' => 'required',
            'rating' => ['required', 'integer', Rule::in(collect(VatsimRating::cases())->filter(function ($rating) {
                return !in_array($rating, [VatsimRating::INA, VatsimRating::SUS, VatsimRating::OBS, VatsimRating::SUP, VatsimRating::ADM]);
            })->pluck('value')->toArray())],
            'area_id' => 'required|integer',
        ]);

        $position = Position::create($data);

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' created successfully.');
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'callsign' => 'required|unique:positions,callsign,' . $position->id,
            'name' => 'required',
            'frequency' => 'required',
            'fir' => 'required',
            'rating' => ['required', 'integer', Rule::in(collect(VatsimRating::cases())->filter(function ($rating) {
                return !in_array($rating, [VatsimRating::INA, VatsimRating::SUS, VatsimRating::OBS, VatsimRating::SUP, VatsimRating::ADM]);
            })->pluck('value')->toArray())],
            'area_id' => 'required|integer',
        ]);

        $position->update($data);

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' updated successfully.');
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Position ' . $position->callsign . ' deleted successfully.');
    }
}
