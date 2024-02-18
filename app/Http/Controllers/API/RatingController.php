<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;

class RatingController extends Controller
{
    public function index()
    {
        $data = collect();

        foreach (User::has('endorsements')->get() as $user) {
            $publishUser = false;
            $ratings = collect();

            foreach ($user->endorsements->where('type', 'MASC') as $endorsement) {
                $ratings->push($endorsement->ratings->pluck('name')->implode(''));
                $publishUser = true;
            }

            if ($publishUser) {
                $data->push([
                    'user_id' => $user->id,
                    'user_atc_active' => boolval($user->atc_active),
                    'ratings' => $ratings,
                ]);
            }
        }

        return response()->json(['data' => $data->values()], 200);
    }
}
