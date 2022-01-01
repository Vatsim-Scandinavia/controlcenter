<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class VisitingController extends Controller
{
    public function index() {
        $users = User::all();

        $visiting_controllers = collect();

        foreach ($users as $user) {
            if ($user->visiting_controller) {
                $visiting_controllers->push([
                    'id' => $user->id,
                    'subdivision' => $user->subdivision,
                    'rating' => $user->rating,
                    'endorsements' => null
                ]);
            }
        }

        return response()->json([
            'visiting_controllers' => $visiting_controllers,
        ], 200);
    }
}
