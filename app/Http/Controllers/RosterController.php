<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Area;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($areaId)
    {

        $area = Area::find($areaId);
        $users = User::allActiveInArea($area);
        $qualificationPeriod = Setting::get('atcActivityQualificationPeriod', 12);

        $visitingUsers = User::whereHas('endorsements', function ($query) use ($areaId) {
            $query->where('type', 'VISITING')->where('revoked', false)->whereHas('areas', function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            });
        })->get();

        $users = $users->merge($visitingUsers);

        // Get ratings that are not VATSIM ratings which belong to the area
        $ratings = Rating::whereHas('areas', function (Builder $query) use ($areaId) {
            $query->where('area_id', $areaId);
        })->whereNull('vatsim_rating')->get()->sortBy('name');

        return view('roster', compact('users', 'ratings', 'area', 'qualificationPeriod'));
    }
}
