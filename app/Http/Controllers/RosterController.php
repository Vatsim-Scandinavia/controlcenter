<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rating;
use App\Models\Area;
use Illuminate\Database\Eloquent\Builder;
use anlutro\LaravelSettings\Facade as Setting;


class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($areaId)
    {

        $area = Area::find($areaId);
        $users = User::allActiveInArea($area);

        // Get ratings that are not VATSIM ratings which belong to the area
        $ratings = Rating::whereHas('areas', function (Builder $query) use ($areaId) {
            $query->where('area_id', $areaId);
        })->whereNull('vatsim_rating')->get()->sortBy('name');

        return view('roster', compact('users', 'ratings', 'area'));
    }
}
