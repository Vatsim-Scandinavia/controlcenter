<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index() {
        $users = User::all();

        $mentors = collect();

        foreach ($users as $user) {
            if ($user->isMentor()) {
                $mentors->push([
                    'id' => $user->id,
                    'fir' => $user->getInlineMentoringAreas()
                ]);
            }
        }

        return response()->json(["data"=> $mentors->values()], 200);
    }
}
