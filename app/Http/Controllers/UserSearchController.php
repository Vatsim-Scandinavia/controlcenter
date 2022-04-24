<?php

namespace App\Http\Controllers;

use App\Models\Handover;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (strlen($query) >= 2) {
            $data = Handover::query()
                ->select('id')
                ->where(DB::raw('LOWER(users.id)'), 'like', '%'.strtolower($query).'%')
                ->orWhere(DB::raw('LOWER(CONCAT(users.first_name, " ", users.last_name))'), 'like', '%'.strtolower($query).'%')
                ->get();

            if ($data->count() <= 0)
                return;

            $authUser = Auth::user();

            $count = 0;
            foreach($data as $handover) {
                if ($count >= 10)
                    break;

                $user = $handover->user;
                if ($authUser->can('view', $user)) {
                    $output[] = ['id' => $user->id, 'name' => $user->name];
                    $count++;
                }
            }

            return json_encode($output);
        }
    }
}
