<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Endorsement;
use Carbon\Carbon;

class TrainingController extends Controller
{
    public function indexSolo() {
        $data = collect();

        $endorsements = Endorsement::where('type', 'SOLO')
        ->where(function($q) {
            $q->orWhere(function($q2){
                $q2->where('expired', false)
                ->where('revoked', false);
            })
            ->orWhere(function($q2){
                $q2->where(function($q3){
                    $q3->where('valid_to', '>=', Carbon::now()->subDays(14));
                })
                ->where(function($q3){
                    $q3->where('expired', true)
                    ->orWhere('revoked', true);
                });
            });
        })
        ->get();

        foreach($endorsements as $endorsement){
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

        $endorsements = Endorsement::where('type', 'S1')
        ->where(function($q) {
            $q->orWhere(function($q2){
                $q2->where('expired', false)
                ->where('revoked', false);
            })
            ->orWhere(function($q2){
                $q2->where(function($q3){
                    $q3->where('valid_to', '>=', Carbon::now()->subDays(14));
                })
                ->where(function($q3){
                    $q3->where('expired', true)
                    ->orWhere('revoked', true);
                });
            });
        })
        ->get();

        foreach($endorsements as $endorsement){
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
