<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Endorsement;

class VisitingController extends Controller
{
    public function index()
    {
        $data = collect();

        foreach (Endorsement::where('type', 'VISITING')->where('revoked', false)->get() as $endorsement) {
            $areas = collect();
            foreach ($endorsement->areas as $area) {
                $maes = collect();
                foreach ($area->ratings->whereNull('vatsim_rating') as $r) {
                    foreach ($endorsement->user->endorsements->where('type', 'MASC') as $mascEndorsement) {
                        if ($r->id == $mascEndorsement->ratings->first()->id) {
                            $maes->push($r->name);
                        }
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

        // Sort alphabetically the table
        $data = $data->sortBy('first_name');

        return response()->json(['data' => $data->values()], 200);
    }
}
