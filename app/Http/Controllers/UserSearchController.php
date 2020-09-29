<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;

class UserSearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function action(Request $request)
    {
        $output = [];

        $query = $request->get('query');

        $data = Auth::user()->viewableModels(\App\User::class);
        $count = 0;
        if($data->count() > 0 && strlen($query) >= 2) {
            foreach($data as $user)
            {
                if($count >= 10) break;
                if (stripos($user->name, (string)$query) !== false || $user->id == (int)$query) {
                    array_push($output, ['id' => $user->id, 'name' => $user->name]);
                    $count++;
                }
            }

            echo json_encode($output);
        }

    }
}
