<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\Country;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('user.overview');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        $groups = Group::all();
        $countries = Country::all();

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;

        return view('user.show', compact('user', 'groups', 'countries', 'trainings', 'statuses', 'types'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {

        $data = $request->validate([
            'access' => 'required|integer',
            'countries' => 'nullable|array'
        ]);

        if (key_exists('countries', $data)) {

            foreach ((array) $data['countries'] as $country) {
                if (!$user->mentor_countries->contains($country)){
                    $user->mentor_countries()->attach($country);
                }
            }

            unset($data['countries']);
        }

        return redirect(route('user.show', $user))->with("success", "User access settings successfully updated.");

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
