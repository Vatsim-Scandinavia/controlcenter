<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Endorsement;

class VisitingController extends Controller
{
    public function index() {
        $data = collect();

        foreach(Endorsement::where('type', 'VISITING')->get() as $endorsement){

            $areas = collect();
            foreach($endorsement->areas as $area){

                $maes = collect();
                foreach($endorsement->ratings->whereNull('vatsim_rating') as $r){
                    if($r->areas->first()->pivot->area_id == $area->id){
                        $maes->push($r->name);
                    }
                }

                $areas->push([
                    'name' => $area->name,
                    'maes' => $maes,
                ]);
            }

            $data->push([
                'user_id' => $endorsement->user->id,
                'first_name' => $endorsement->user->first_name,
                'rating' => $endorsement->ratings->whereNotNull('vatsim_rating')->first()->name,
                'areas' => $areas,
            ]);
        }

        return response()->json(["data"=> $data->values()], 200);
    }
}
