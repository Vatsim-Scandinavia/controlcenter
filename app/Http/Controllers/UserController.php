<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\Vatsim;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Group;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Http\Requests\StatsimAtcSessionsRequest;
use App\Services\StatsimService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Controller to handle user views
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', \Auth::user());

        $users = [];

        if (config('vatsim.core_api_token')) {
            $response = $this->fetchUsersFromVatsimCoreApi();
            if ($response === false) {
                return view('user.index', compact('users'))->withErrors('Error fetching users from VATSIM Core API. Check if your token is correct.');
            }
        } else {
            return view('user.index', compact('users'))->withErrors('Enable VATSIM Core API Integration to enable this feature.');
        }

        $apiUsers = [];
        $ccUsers = User::pluck('id');
        $ccUsersHours = AtcActivity::all();
        $ccUsersActive = User::getActiveAtcMembers()->pluck('id');

        if (config('vatsim.core_api_token')) {
            foreach ($response as $data) {
                $apiUsers[$data['id']] = $data;
            }
        } else {
            // Only include users from the division and index by key
            foreach ($response as $data) {
                if ($data[config('app.mode')] == config('app.owner_code')) {
                    $apiUsers[$data['id']] = $data;
                }
            }
        }

        // Merge the data sources
        $users = [];
        foreach ($apiUsers as $apiUser) {
            $users[$apiUser['id']] = $apiUser;

            if (in_array($apiUser['id'], $ccUsers->toArray())) {
                $users[$apiUser['id']]['cc_data'] = true;
                $users[$apiUser['id']]['active'] = false;

                if (isset($ccUsersHours->where('user_id', $apiUser['id'])->first()->hours)) {
                    $users[$apiUser['id']]['hours'] = $ccUsersHours->where('user_id', $apiUser['id'])->first()->hours;
                }

                if (in_array($apiUser['id'], $ccUsersActive->toArray())) {
                    $users[$apiUser['id']]['active'] = true;
                }
            }
        }

        return view('user.index', compact('users'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexOther()
    {
        $this->authorize('index', \Auth::user());

        if (config('app.mode') == 'subdivision') {
            $subdivisions = array_map('trim', explode(',', Setting::get('trainingSubDivisions')));
            $users = User::whereNotIn('subdivision', $subdivisions)->get();
        } else {
            $users = User::whereNot('division', config('app.owner_code'))->get();
        }

        return view('user.other', compact('users'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $groups = Group::all();
        $areas = Area::all();

        if ($user == null) {
            return abort(404);
        }

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $endorsements = $user->endorsements->whereIn('type', ['EXAMINER', 'FACILITY', 'SOLO', 'VISITING'])->sortBy([['expired', 'asc'], ['revoked', 'asc']]);

        // Get hours and grace per area
        $atcActivityHours = [];
        $totalHours = 0;
        $atcActivites = AtcActivity::where('user_id', $user->id)->get();

        foreach ($areas as $area) {
            $activity = $atcActivites->firstWhere('area_id', $area->id);

            if ($activity) {

                $atcActivityHours[$area->id]['hours'] = $activity->hours;
                $totalHours += $activity->hours;

                if ($activity->start_of_grace_period) {
                    $atcActivityHours[$area->id]['graced'] = $activity->start_of_grace_period->addMonths((int) Setting::get('atcActivityGracePeriod', 12))->gt(now());
                } else {
                    $atcActivityHours[$area->id]['graced'] = false;
                }

                $atcActivityHours[$area->id]['active'] = ($activity->atc_active) ? true : false;

            } else {
                $atcActivityHours[$area->id]['hours'] = 0;
                $atcActivityHours[$area->id]['active'] = false;
                $atcActivityHours[$area->id]['graced'] = false;
            }
        }

        // Fetch division exams
        $divisionExams = collect();
        $userExams = DivisionApi::getUserExams($user);
        if ($userExams && $userExams->successful()) {

            foreach ($userExams->json()['data'] as $category => $categories) {
                foreach ($categories as $exam) {
                    $exam['category'] = $category;
                    $exam['rating'] = DivisionApi::getUserExamRating((int) $exam['flag_exam_type']);
                    $exam['created_at'] = Carbon::parse($exam['created_at'])->toEuropeanDate();
                    $divisionExams->push($exam);
                }
            }

            // Sort all entries by created_at
            $divisionExams = $divisionExams->sortByDesc('created_at');
        }

        return view('user.show', compact('user', 'groups', 'areas', 'trainings', 'statuses', 'types', 'endorsements', 'areas', 'divisionExams', 'atcActivityHours', 'totalHours'));
    }

    /**
     * AJAX: Search for the user by name or ID
     *
     * @return array
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function search(Request $request)
    {
        $output = [];

        $query = $request->get('query');

        if (strlen($query) >= 2) {
            $data = User::query()
                ->select(['id', 'first_name', 'last_name'])
                ->where(DB::raw('LOWER(id)'), 'like', '%' . strtolower($query) . '%')
                ->orWhere(DB::raw('LOWER(CONCAT(first_name, " ", last_name))'), 'like', '%' . strtolower($query) . '%')
                ->orderByDesc('last_login')
                ->get();

            if ($data->count() <= 0) {
                return;
            }

            $authUser = Auth::user();

            $count = 0;
            foreach ($data as $user) {
                if ($count >= 10) {
                    break;
                }

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
     * @return array
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function fetchVatsimHours(Request $request)
    {
        $cid = $request['cid'];

        $vatsimStats = [];
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', 'https://api.vatsim.net/v2/members/' . $cid . '/stats');
            if ($res->getStatusCode() == 200) {
                $vatsimStats = json_decode($res->getBody(), false);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json(['data' => null], 404);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $vatsimStats], 200);
    }

    /**
     * AJAX: Return ATC sessions from StatSim for user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchStatsimAtcSessions(StatsimAtcSessionsRequest $request)
    {
        $service = app(StatsimService::class);
        $sessions = $service->getAtcSessions(
            $request->validated()['vatsimId'],
            $request->validated()['from'],
            $request->validated()['to']
        );

        $transformed = $service->transformSessions($sessions);

        return response()->json($transformed);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $permissions = [];

        // Generate a list of possible validations
        foreach (Area::all() as $area) {
            foreach (Group::all() as $group) {
                // Don't list or allow admin rank to be set through this interface
                if ($group->id == 1) {
                    continue;
                }

                // Only process ranks the user is allowed to change
                if (! \Illuminate\Support\Facades\Gate::inspect('updateGroup', [$user, $group, $area])->allowed()) {
                    continue;
                }

                $key = $area->id . '_' . $group->name;
                $permissions[$key] = '';
            }
        }

        // Valiate and allow these fields, then loop through permissions to set the final data set
        $data = $request->validate($permissions);
        foreach ($permissions as $key => $value) {
            isset($data[$key]) ? $permissions[$key] = true : $permissions[$key] = false;
        }

        // Check and update the permissions
        foreach ($permissions as $key => $value) {
            $str = explode('_', $key);

            $area = Area::where('id', $str[0])->get()->first();
            $group = Group::where('name', $str[1])->get()->first();

            // Check if permission is not set, and set it or other way around.
            if ($user->groups()->where('area_id', $area->id)->where('group_id', $group->id)->get()->count() == 0) {
                if ($value == true) {
                    $this->authorize('updateGroup', [$user, $group, $area]);

                    // Call the division API to assign mentor
                    if ($group->id == 3) {
                        $response = DivisionApi::assignMentor($user, Auth::id());
                        if ($response && $response->failed()) {
                            return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                        }
                    }

                    // Attach the new permission
                    $user->groups()->attach($group, ['area_id' => $area->id, 'inserted_by' => Auth::id()]);
                }
            } else {
                if ($value == false) {
                    $this->authorize('updateGroup', [$user, $group, $area]);

                    // Call the division API to assign mentor
                    if ($group->id == 3) {
                        $response = DivisionApi::removeMentor($user, Auth::id());
                        if ($response && $response->failed()) {
                            return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                        }
                    }

                    // Detach the permission
                    $user->groups()->wherePivot('area_id', $area->id)->wherePivot('group_id', $group->id)->detach();
                }
            }

            // Check and detach trainings from mentor
            if ($user->teaches()->where('area_id', $area->id)->count() > 0 && ! $user->isMentor() && $value == false) {
                $user->teaches()->detach($user->teaches->where('area_id', $area->id));
            }
        }

        return redirect(route('user.show', $user))->with('success', 'User access settings successfully updated.');
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
            'setting_notify_tasks' => '',
            'setting_workmail_address' => 'nullable|email|max:64|regex:/(.*)' . Setting::get('linkDomain') . '$/i',
            'setting_theme' => 'required|in:light,dark,system',
        ]);

        isset($data['setting_notify_newreport']) ? $setting_notify_newreport = true : $setting_notify_newreport = false;
        isset($data['setting_notify_newreq']) ? $setting_notify_newreq = true : $setting_notify_newreq = false;
        isset($data['setting_notify_closedreq']) ? $setting_notify_closedreq = true : $setting_notify_closedreq = false;
        isset($data['setting_notify_newexamreport']) ? $setting_notify_newexamreport = true : $setting_notify_newexamreport = false;
        isset($data['setting_notify_tasks']) ? $setting_notify_tasks = true : $setting_notify_tasks = false;

        $user->setting_notify_newreport = $setting_notify_newreport;
        $user->setting_notify_newreq = $setting_notify_newreq;
        $user->setting_notify_closedreq = $setting_notify_closedreq;
        $user->setting_notify_newexamreport = $setting_notify_newexamreport;
        $user->setting_notify_tasks = $setting_notify_tasks;
        $user->setting_theme = $data['setting_theme'];

        if (! $user->setting_workmail_address && isset($data['setting_workmail_address'])) {
            $user->setting_workmail_address = $data['setting_workmail_address'];
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
        } elseif ($user->setting_workmail_address && ! isset($data['setting_workmail_address'])) {
            $user->setting_workmail_address = null;
            $user->setting_workmail_expire = null;
        }

        $user->save();

        return redirect()->intended(route('user.settings'))->withSuccess('Settings successfully changed');
    }

    /**
     * Display a listing of user's reports
     *
     * @return \Illuminate\Http\Response
     */
    public function reports(Request $request, User $user)
    {
        $this->authorize('viewReports', $user);

        $viewingUser = Auth::user();
        $examinations = $viewingUser->viewableModels(TrainingExamination::class, [['examiner_id', '=', $user->id]]);
        $reports = $viewingUser->viewableModels(TrainingReport::class, [['written_by_id', '=', $user->id]]);

        $reportsAndExams = collect($reports)->merge($examinations);
        $reportsAndExams = $reportsAndExams->sort(function ($a, $b) {
            // Define the correct date to sort by model type is report or exam
            is_a($a, '\App\Models\TrainingReport') ? $aSort = Carbon::parse($a->report_date) : $aSort = Carbon::parse($a->examination_date);
            is_a($b, '\App\Models\TrainingReport') ? $bSort = Carbon::parse($b->report_date) : $bSort = Carbon::parse($b->examination_date);

            // Sorting algorithm
            if ($aSort == $bSort) {
                return (is_a($a, '\App\Models\TrainingExamination')) ? -1 : 1;
            }

            return ($aSort > $bSort) ? -1 : 1;
        });

        return view('user.reports', compact('user', 'reportsAndExams'));
    }

    /**
     * Renew 30 days on the workmail address
     *
     * @return \Illuminate\Http\Response
     */
    public function extendWorkmail()
    {
        $user = Auth::user();

        if (Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7) {
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
            $user->save();

            return redirect()->intended(route('user.settings'))->withSuccess('Workmail successfully extended');
        } else {
            return redirect()->intended(route('user.settings'))->withErrors('Workmail is not due to expire');
        }
    }

    /**
     * Fetch users from VATSIM Core API
     *
     * @return \Illuminate\Http\Response|bool
     */
    private function fetchUsersFromVatsimCoreApi()
    {
        $url = sprintf('https://api.vatsim.net/v2/orgs/%s/%s', config('app.mode'), config('app.owner_code'));
        $headers = [
            'X-API-Key' => config('vatsim.core_api_token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $users = [];
        $usersCount = 0;

        $limit = 1000;
        $count = -1;

        do {
            $response = Http::withHeaders($headers)->get(sprintf('%s?include_inactive=1&limit=%s&offset=%s', $url, $limit, $usersCount));

            if (! $response->successful()) {
                return false;
            }

            $jsonResponse = $response->json();

            if ($count == -1) {
                $count = $jsonResponse['count'];
            }

            $users = array_merge($users, $jsonResponse['items']);
            $usersCount = count($users);
        } while ($usersCount < $count);

        return $users;
    }
}
