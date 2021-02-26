<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\Country;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Inline\Parser\NewlineParser;

/**
 * Controller to handle user views
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', \Auth::user());

        $users = User::all();
        return view('user.index', compact('users'));
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
        $areas = Country::all();

        if ($user == null)
            return abort(404);

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $endorsements = $user->ratings;

        return view('user.show', compact('user', 'groups', 'areas', 'trainings', 'statuses', 'types', 'endorsements'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, User $user)
    {

        $this->authorize('update', $user);
        $permissions = [];

        // Generate a list of possible validations
        foreach(Country::all() as $country){
            foreach(Group::all() as $group){
                // Don't list or allow admin ranks to be set through this interface
                if($group->id == 1) { continue; }

                $key = $country->name.'_'.$group->name;
                $permissions[$key] = '';
            }
        }

        // Valiate and allow these fields, then loop through permissions to set the final data set
        $data = $request->validate($permissions);
        foreach($permissions as $key => $value){
            isset($data[$key]) ? $permissions[$key] = true : $permissions[$key] = false;
        }

        // Check and update the permissions
        foreach($permissions as $key => $value){
        
            $str = explode('_', $key);

            $country_id = Country::where('name', $str[0])->get()->first()->id;
            $group_id = Group::where('name', $str[1])->get()->first()->id;

            // Check if permission is not set, and set it or other way around.
            if($user->permissions->where('country_id', $country_id)->where('group_id', $group_id)->count() == 0){
                if($value == true){

                    $newPermission = new Permission([
                        'user_id' => $user->id,
                        'country_id' => $country_id,
                        'group_id' => $group_id,
                    ]);
                    $newPermission->inserted_by = Auth::id();

                    $user->permissions()->save($newPermission);

                }
            } else {
                if($value == false){
                    Permission::where('user_id', $user->id)->where('country_id', $country_id)->where('group_id', $group_id)->delete();
                }
            }
        
        }

        $user->save();

        return redirect(route('user.show', $user))->with("success", "User access settings successfully updated.");

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
     * @param  \App\Models\User  $user
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
