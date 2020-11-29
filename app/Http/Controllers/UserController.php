<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('index', \Auth::user());

        $users = User::all();
        return view('user.index', compact('users'));
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
     * @param User $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $groups = Group::all();
        $countries = Country::all();

        if ($user == null)
            return abort(404);

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $endorsements = $user->ratings;

        return view('user.show', compact('user', 'groups', 'countries', 'trainings', 'statuses', 'types', 'endorsements'));
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

        $this->authorize('update', $user);

        $data = $request->validate([
            'access' => 'required|integer',
            'countries' => 'nullable|array'
        ]);

        if (key_exists('countries', $data)) {

            foreach ((array) $data['countries'] as $country) {
                if (!$user->training_role_countries->contains($country)){
                    $user->training_role_countries()->attach($country);
                }
            }

            foreach ($user->training_role_countries as $country) {
                if (!in_array($country->id, (array) $data['countries'])) {
                    $user->training_role_countries()->detach($country);

                    // Unassign this mentor from trainings from the specific country
                    $user->teaches()->detach($user->teaches->where('country_id', $country->id));
                }
            }

            unset($data['countries']);
        } else {
            // Detach all if no passed key, as that means the list is empty
            $user->training_role_countries()->detach();

            // Unassign this mentor from all trainings
            $user->teaches()->detach();
        }

        if($data['access'] == 0){
            $user->group = null;

            // Detach all country assosiciations if they are downgraded all the way to student.
            $user->training_role_countries()->detach();

            // Unassign this mentor from all trainings
            $user->teaches()->detach();
        } else {
            $user->group = $data['access'];
        }

        $user->save();

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



    /**
     * Display a listing of user's settings
     *
     * @return \Illuminate\Http\Response
     */
    public function settings()
    {
        $user = Auth::user();
        return view('usersettings', compact('user'));
    }

    /**
     * Update the user's settings to storage
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function settings_update(Request $request, User $user)
    {
        $user = Auth::user();

        $data = $request->validate([
            'setting_notify_newreport' => '',
            'setting_notify_newreq' => '',
            'setting_notify_closedreq' => '',
            'setting_notify_newexamreport' => ''
        ]);

        isset($data['setting_notify_newreport']) ? $setting_notify_newreport = true : $setting_notify_newreport = false;
        isset($data['setting_notify_newreq']) ? $setting_notify_newreq = true : $setting_notify_newreq = false;
        isset($data['setting_notify_closedreq']) ? $setting_notify_closedreq = true : $setting_notify_closedreq = false;
        isset($data['setting_notify_newexamreport']) ? $setting_notify_newexamreport = true : $setting_notify_newexamreport = false;

        $user->setting_notify_newreport = $setting_notify_newreport;
        $user->setting_notify_newreq = $setting_notify_newreq;
        $user->setting_notify_closedreq = $setting_notify_closedreq;
        $user->setting_notify_newexamreport = $setting_notify_newexamreport;
        $user->save();

        return redirect()->intended(route('user.settings'))->withSuccess("Settings successfully changed");
    }

}
