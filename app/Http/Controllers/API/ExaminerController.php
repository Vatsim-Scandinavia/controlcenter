<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Area;

class ExaminerController extends Controller
{
    public function index() {

        $areas = collect();
        foreach(Area::all() as $area){
            $thisArea = collect();
            
            foreach($area->endorsements->where('type', 'EXAMINER') as $endorsement){
                $thisArea->push([
                    'user_id' => $endorsement->user->id,
                    'first_name' => $endorsement->user->first_name,
                    'rating' => $endorsement->ratings->first()->name,
                ]);
            }

            $areas->push([
                'area' => $area->name,
                'examiners' => $thisArea
            ]);
        }

        return response()->json(["data"=> $areas->values()], 200);
    }
}
