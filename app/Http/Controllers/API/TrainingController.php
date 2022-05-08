<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Endorsement;

class TrainingController extends Controller
{
    public function indexSolo() {
        $data = collect();

        foreach(Endorsement::where('type', 'SOLO')->get() as $endorsement){
            $data->push([
                'user_id' => $endorsement->user->id,
                'type' => $endorsement->type,
                'valid_from' => $endorsement->valid_from,
                'valid_to' => $endorsement->valid_to,
                'expired' => boolval($endorsement->expired),
                'revoked' => boolval($endorsement->revoked),
                'positions' => $endorsement->positions->pluck('callsign'),
            ]);
        }

        return response()->json(["data"=> $data->values()], 200);
    }

    public function indexS1() {
        $data = collect();

        foreach(Endorsement::where('type', 'S1')->get() as $endorsement){
            $data->push([
                'user_id' => $endorsement->user->id,
                'type' => $endorsement->type,
                'valid_from' => $endorsement->valid_from,
                'valid_to' => $endorsement->valid_to,
                'expired' => boolval($endorsement->expired),
                'revoked' => boolval($endorsement->revoked),
                'positions' => $endorsement->positions->pluck('callsign'),
            ]);
        }

        return response()->json(["data"=> $data->values()], 200);
    }
}
