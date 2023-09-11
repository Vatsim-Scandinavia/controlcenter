<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Area;

class StudentsController extends Controller
{
    public function index()
    {
        $data = [];        
        $areas = Area::all();
        
        foreach($areas as $area){
            $data[$area->name] = [];

            $ratings = $area->ratings->whereNotNull('vatsim_rating');

            foreach($ratings as $rating){                
                $data[$area->name][$rating->name] = [];

                $userIds = $rating->trainings->where('status', '>=', 1)->where('area_id', $area->id)->pluck('user.id');
                if($userIds->count()){
                    $data[$area->name][$rating->name] = $userIds->toArray();
                }
            }
        }

        return response()->json(['data' => [
            $data
        ]], 200);
    }
}
