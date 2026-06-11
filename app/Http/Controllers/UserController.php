<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Exceptions\StatisticsApiException;
use App\Facades\DivisionApi;
use App\Helpers\Vatsim;
use App\Http\Requests\StatisticsSessionsRequest;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Services\StatisticsService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Controller to handle user views
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     *
     * @throws AuthorizationException
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
     * @return View
     *
     * @throws AuthorizationException
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
     * @return Application|Factory|View|void
     *
     * @throws AuthorizationException
     */
    public function show(User $user, StatisticsService $statisticsService)
    {
        $this->authorize('view', $user);

        $roles = config('roles.roles');
        $areas = Area::all();

        if ($user == null) {
            return abort(404);
        }

        $user->load([
            'roleAssignments',
            'trainings.ratings',
            'endorsements.ratings',
            'endorsements.positions',
            'endorsements.areas',
            'endorsements.issuedBy',
            'endorsements.revokedBy',
        ]);

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

        // Fetch recent ATC sessions from StatSim via the StatisticsService.
        // Use the same general window as the activity chart (last 12 months)
        // and then narrow to a configurable \"recent\" period for the table.
        $to = Carbon::now()->endOfDay();
        $from = (clone $to)->subMonths(11)->startOfDay();

        $recentAtcSessions = collect(
            $statisticsService->getRecentSessionsSummary(
                (string) $user->id,
                $from,
                $to
            )
        );

        return view('user.show', compact('user', 'roles', 'areas', 'trainings', 'statuses', 'types', 'endorsements', 'areas', 'divisionExams', 'atcActivityHours', 'totalHours', 'recentAtcSessions'));
    }

    /**
     * AJAX: Search for the user by name or ID
     *
     * @return array
     *
     * @throws AuthorizationException
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
     */
    public function fetchVatsimHours(Request $request): JsonResponse
    {
        $cid = $request['cid'];

        $vatsimStats = [];
        try {
            $client = new Client();
            $res = $client->request('GET', 'https://api.vatsim.net/v2/members/' . $cid . '/stats');
            if ($res->getStatusCode() == 200) {
                $vatsimStats = json_decode($res->getBody(), false);
            }
        } catch (RequestException $e) {
            return response()->json(['data' => null], 404);
        } catch (ClientException $e) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $vatsimStats], 200);
    }

    /**
     * AJAX: Return ATC sessions from statistics API for user
     */
    public function fetchStatisticsSessions(StatisticsSessionsRequest $request, User $user, StatisticsService $service): JsonResponse
    {
        try {
            $sessions = $service->getCachedAtcSessions(
                $user->id,
                $request->date('from'),
                $request->date('to')
            );

            $transformed = $service->transformSessions($sessions);

            return response()->json($transformed);
        } catch (StatisticsApiException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => $e->getHttpStatus() ?: 500,
            ], $e->getHttpStatus() ?: 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, User $user): SymfonyResponse
    {
        $this->authorize('update', $user);
        $permissions = [];

        // Generate a list of possible validations: one key per area/role cell,
        // plus the global row (area-less assignments) keyed as global_{role}
        foreach (Area::all() as $area) {
            foreach (config('roles.roles') as $roleKey => $role) {
                // Only process ranks the user is allowed to change
                if (! Gate::inspect('updateRole', [$user, $roleKey, $area])->allowed()) {
                    continue;
                }

                $key = $area->id . '_' . $roleKey;
                $permissions[$key] = '';
            }
        }

        foreach (config('roles.roles') as $roleKey => $role) {
            if (! Gate::inspect('updateRole', [$user, $roleKey, null])->allowed()) {
                continue;
            }

            $permissions['global_' . $roleKey] = '';
        }

        // Valiate and allow these fields, then loop through permissions to set the final data set
        $data = $request->validate($permissions);
        foreach ($permissions as $key => $value) {
            isset($data[$key]) ? $permissions[$key] = true : $permissions[$key] = false;
        }

        // Check and update the permissions
        foreach ($permissions as $key => $value) {
            [$scopeKey, $roleKey] = explode('_', $key, 2);
            $area = $scopeKey === 'global' ? null : Area::find($scopeKey);

            $assignments = $user->roleAssignments()->where('role', $roleKey)->when(
                $area === null,
                fn ($query) => $query->whereNull('area_id'),
                fn ($query) => $query->where('area_id', $area->id),
            );

            // Check if permission is not set, and set it or other way around.
            if ($assignments->count() == 0) {
                if ($value == true) {
                    $this->authorize('updateRole', [$user, $roleKey, $area]);

                    // Call the division API to assign mentor
                    if ($roleKey == 'mentor') {
                        $response = DivisionApi::assignMentor($user, Auth::id());
                        if ($response && $response->failed()) {
                            return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                        }
                    }

                    // Attach the new permission
                    $user->roleAssignments()->create(['role' => $roleKey, 'area_id' => $area?->id]);
                }
            } else {
                if ($value == false) {
                    $this->authorize('updateRole', [$user, $roleKey, $area]);

                    // Call the division API to assign mentor
                    if ($roleKey == 'mentor') {
                        $response = DivisionApi::removeMentor($user, Auth::id());
                        if ($response && $response->failed()) {
                            return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                        }
                    }

                    // Detach the permission
                    $assignments->delete();
                }
            }

            // Check and detach trainings from mentor
            if ($area !== null && $user->teaches()->where('area_id', $area->id)->count() > 0 && ! $user->hasRole('mentor') && $value == false) {
                $user->teaches()->detach($user->teaches->where('area_id', $area->id));
            }
        }

        return redirect(route('user.show', $user))->with('success', 'User access settings successfully updated.');
    }

    /**
     * Display a listing of user's settings
     *
     * @return Response
     */
    public function settings()
    {
        $user = Auth::user();

        return view('usersettings', compact('user'));
    }

    /**
     * Update the user's settings to storage
     *
     * @return Response
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
     * @return Response
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
     * @return Response
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
     * @return Response|bool
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
