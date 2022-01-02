<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index() {
        $users = User::all();

        $mentors = collect();

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
        }

        return response()->json(["data"=> $mentors->values()], 200);
    }
}
