<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * Controller for handling internal API request to search up users for search bar
 */
class UserSearchController extends Controller
{
    /**
     * Run the request to database and echo the results out directly
     *
     * @return void
     */
    function action(Request $request)
    {
        $output = [];

        $query = $request->get('query');

        $data = Auth::user()->viewableModels(\App\Models\User::class);
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
