<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Endorsement;

class ExaminerController extends Controller
{
    public function index() {
        $data = collect();

        foreach(Endorsement::where('type', 'EXAMINER')->get() as $endorsement){
            $data->push([
                'user_id' => $endorsement->user->id,
                'first_name' => $endorsement->user->first_name,
                'ratings' => $endorsement->ratings->pluck('name'),
                'areas' => $endorsement->areas->pluck('name'),
            ]);
        }

        return response()->json(["data"=> $data->values()], 200);
    }
}
