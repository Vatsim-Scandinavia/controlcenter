<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\Area;
use App\Models\Handover;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Inline\Parser\NewlineParser;
use Illuminate\Support\Facades\DB;
use anlutro\LaravelSettings\Facade as Setting;

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
        $userHours = DB::table('atc_activity')->get();

        return view('user.index', compact('users', 'userHours'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexOther()
    {
        $this->authorize('index', \Auth::user());

        $users = User::all();

        return view('user.other', compact('users'));
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
        $areas = Area::all();

        if ($user == null)
            return abort(404);

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $endorsements = $user->endorsements->sortByDesc('valid_to');
        $userHours = DB::table('atc_activity')->where('user_id', $user->id)->first();
        if(isset($userHours)) $userHours = $userHours->atc_hours;

        return view('user.show', compact('user', 'groups', 'areas', 'trainings', 'statuses', 'types', 'endorsements', 'userHours'));
    }

    /**
     * AJAX: Search for the user by name or ID
     * @param Request $request
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function search(Request $request)
    {
        $output = [];

        $query = $request->get('query');

        if (strlen($query) >= 2) {
            $data = Handover::query()
                ->select('id')
                ->where(DB::raw('LOWER(id)'), 'like', '%'.strtolower($query).'%')
                ->orWhere(DB::raw('LOWER(CONCAT(first_name, " ", last_name))'), 'like', '%'.strtolower($query).'%')
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

    /**
     * AJAX: Return ATC hours from VATSIM for user
     * 
     * @param Request $request
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
    */
    public function fetchVatsimHours(Request $request){
        $cid = $request['cid'];

        $vatsimStats = [];
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', 'https://api.vatsim.net/api/ratings/'.$cid.'/rating_times/');
            if($res->getStatusCode() == 200){
                $vatsimStats = json_decode($res->getBody(), false);
            }
        } catch(\GuzzleHttp\Exception\RequestException $e){
            return response()->json(["data" => null], 404);
        } catch(\GuzzleHttp\Exception\ClientException $e){
            return response()->json(["data" => null], 404);
        }

        return response()->json(["data" => $vatsimStats], 200);
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
        foreach(Area::all() as $area){
            foreach(Group::all() as $group){
                // Don't list or allow admin rank to be set through this interface
                if($group->id == 1) { continue; }

                // Only process ranks the user is allowed to change
                if(!\Illuminate\Support\Facades\Gate::inspect('updateGroup', [$user, $group, $area])->allowed()) { continue; }

                $key = $area->id.'_'.$group->name;
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

            $area = Area::where('id', $str[0])->get()->first();
            $group = Group::where('name', $str[1])->get()->first();

            // Check if permission is not set, and set it or other way around.
            if($user->groups()->where('area_id', $area->id)->where('group_id', $group->id)->get()->count() == 0){
                if($value == true){
                    $this->authorize('updateGroup', [$user, $group, $area]);
                    $user->groups()->attach($group, ['area_id' => $area->id, 'inserted_by' => Auth::id()]);
                }
            } else {
                if($value == false){
                    $this->authorize('updateGroup', [$user, $group, $area]);
                    $user->groups()->wherePivot('area_id', $area->id)->wherePivot('group_id', $group->id)->detach();
                }
            }

            // Check and detach trainings from mentor
            if($user->teaches()->where('area_id', $area->id)->count() > 0 && !$user->isMentor() && $value == false){
                $user->teaches()->detach($user->teaches->where('area_id', $area->id));
            }

        }

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
            'setting_notify_newexamreport' => '',
            'setting_workmail_address' => 'nullable|email|max:64|regex:/(.*)'.Setting::get('linkDomain').'$/i',
        ]);

        isset($data['setting_notify_newreport']) ? $setting_notify_newreport = true : $setting_notify_newreport = false;
        isset($data['setting_notify_newreq']) ? $setting_notify_newreq = true : $setting_notify_newreq = false;
        isset($data['setting_notify_closedreq']) ? $setting_notify_closedreq = true : $setting_notify_closedreq = false;
        isset($data['setting_notify_newexamreport']) ? $setting_notify_newexamreport = true : $setting_notify_newexamreport = false;

        $user->setting_notify_newreport = $setting_notify_newreport;
        $user->setting_notify_newreq = $setting_notify_newreq;
        $user->setting_notify_closedreq = $setting_notify_closedreq;
        $user->setting_notify_newexamreport = $setting_notify_newexamreport;
        
        if(!$user->setting_workmail_address && isset($data['setting_workmail_address'])){
            $user->setting_workmail_address = $data['setting_workmail_address'];
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
        } elseif($user->setting_workmail_address && !isset($data['setting_workmail_address'])){
            $user->setting_workmail_address = null;
            $user->setting_workmail_expire = null;
        }

        $user->save();

        return redirect()->intended(route('user.settings'))->withSuccess("Settings successfully changed");
    }

    /**
     * Renew 30 days on the workmail address
     *
     * @return \Illuminate\Http\Response
     */
    public function extendWorkmail()
    {

        $user = Auth::user();

        if(Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7){
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
            $user->save();

            return redirect()->intended(route('user.settings'))->withSuccess("Workmail successfully extended");
        } else {
            return redirect()->intended(route('user.settings'))->withErrors("Workmail is not due to expire");
        }

        
    }

}
