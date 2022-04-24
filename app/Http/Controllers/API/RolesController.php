<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index() {
        $users = User::all();

        $mentors = collect();

        $moderators = collect();

        $admins = collect();

        foreach ($users as $user) {
            if ($user->isMentor()) {

                // Get their areas
                $areas = [];
                foreach($user->groups as $group){
                    if($group->pivot->group_id == 3){
                        array_push($areas, Area::find($group->pivot->area_id)->name);
                    }
                }

                $mentors->push([
                    'id' => $user->id,
                    'fir' => $areas
                ]);
            }
            if($user->isModerator()) {
                $areas = [];
                foreach($user->groups as $group){
                    if($group->pivot->group_id == 2){
                        array_push($areas, Area::find($group->pivot->area_id)->name);
                    }
                }

                $moderators->push([
                    'id' => $user->id,
                    'fir' => $areas
                ]);
            }
            if($user->isAdmin()) {
                $areas = [];
                foreach($user->groups as $group){
                    if($group->pivot->group_id == 1){
                        array_push($areas, Area::find($group->pivot->area_id)->name);
                    }
                }

                $admins->push([
                    'id' => $user->id,
                    'fir' => $areas
                ]);
            }
        }

        return response()->json(["data"=> [
            "mentors" => $mentors->values(),
            "moderators" => $moderators->values(),
            "admins" => $admins->values()
        ]], 200);
    }
}
