<nav>

    <ul class="navbar-nav sidebar" id="sidebar">

        {{-- Sidebar - Brand --}}
        <a class="sidebar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <div class="sidebar-brand-icon">
                <img src="{{ asset('images/control-tower.svg') }}">
            </div>

            <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>

            <button type="button" id="sidebar-button-close" class="sidebar-button-close ms-auto">
                <i class="fas fa-times"></i>
            </button>
        </a>

        {{-- Divider --}}
        <div class="sidebar-divider my-0"></div>

        <li class="nav-item {{ Route::is('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-table-columns"></i>
            <span>Dashboard</span></a>
        </li>

        @can('update', [\App\Models\Task::class])
            <li class="nav-item {{ Route::is('tasks') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tasks') }}">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Tasks</span>
                    @if(\Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count())
                        <span class="badge text-bg-danger">{{ \Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count() }}</span>
                    @endif
                </a>
            </li>
        @endcan

        @can('view', \App\Models\Booking::class)
            <li class="nav-item {{ Route::is('booking*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('booking') }}">
                <i class="fas fa-fw fa-calendar"></i>
                <span>Booking</span></a>
            </li>
        @endcan

        @if(Setting::get('linkMoodle') && Setting::get('linkMoodle') != "")
            <li class="nav-item">
            <a class="nav-link" href="{{ Setting::get('linkMoodle') }}" target="_blank">
                <i class="fas fa-graduation-cap"></i>
                <span>Moodle</span></a>
            </li>
        @endif

        @if (\Auth::user()->isMentorOrAbove())

            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            {{-- Heading --}}
            <div class="sidebar-heading">
            Training
            </div>

            <li class="nav-item {{ Route::is('mentor') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('mentor') }}">
                <i class="fas fa-fw fa-chalkboard-teacher"></i>
                <span>My students</span></a>
            </li>

            <li class="nav-item {{ Route::is('sweatbook') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('sweatbook') }}">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Sweatbox Calendar</span>
                </a>
            </li>

        @endif
        @if (\Auth::user()->isModeratorOrAbove())

            {{-- Nav Item - Pages Collapse Menu --}}
            <li class="nav-item {{ Route::is('requests') || Route::is('requests.history') ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReq" aria-expanded="true" aria-controls="collapseReq">
                <i class="fas fa-fw fa-flag"></i>
                <span>Requests</span>
            </a>
            <div id="collapseReq" class="collapse" data-bs-parent="#sidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('requests') }}">Open Requests</a>
                <a class="collapse-item" href="{{ route('requests.history') }}">Closed Requests</a>
                </div>
            </div>
            </li>

        @endif

        {{-- Divider --}}
        <div class="sidebar-divider"></div>

        {{-- Heading --}}
        <div class="sidebar-heading">
        Members
        </div>

        @if (\Auth::user()->isModeratorOrAbove())

            {{-- Nav Item - Pages Collapse Menu --}}
            <li class="nav-item {{ Route::is('users') || Route::is('users.other') ? 'active' : '' }}">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMem" aria-expanded="true" aria-controls="collapseMem">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Users</span>
                </a>
                <div id="collapseMem" class="collapse" data-bs-parent=".sidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('users') }}">Member Overview</a>
                    <a class="collapse-item" href="{{ route('users.other') }}">Other Users</a>
                    </div>
                </div>
            </li>

        @endif

        {{-- Nav Item - Pages Collapse Menu --}}
        <li class="nav-item {{ Route::is('roster') ? 'active' : '' }}">

            @php
                $areas = \App\Models\Area::all();
            @endphp

            @if($areas->count() > 1)
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseRosters" aria-expanded="true" aria-controls="collapseRosters">
                    <i class="fas fa-fw fa-address-book"></i>
                    <span>ATC Roster</span>
                </a>

                <div id="collapseRosters" class="collapse" data-bs-parent="#sidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                    @foreach($areas as $area)
                        <a class="collapse-item" href="{{ route('roster', $area->id) }}">{{ $area->name }}</a>
                    @endforeach
                    </div>
                </div>
            @else
                <a class="nav-link" href="{{ route('roster', $areas->first()->id) }}">
                    <i class="fas fa-fw fa-address-book"></i>
                    <span>ATC Roster</span>
                </a>
            @endif

        </li>

        {{-- Nav Item - Pages Collapse Menu --}}
        <li class="nav-item {{ Route::is('endorsements.*') ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseEndorsements" aria-expanded="true" aria-controls="collapseEndorsements">
                <i class="fas fa-fw fa-check-square"></i>
                <span>Endorsements</span>
            </a>
            <div id="collapseEndorsements" class="collapse" data-bs-parent="#sidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('endorsements.solos') }}">Solo</a>
                <a class="collapse-item" href="{{ route('endorsements.examiners') }}">Examiner</a>
                <a class="collapse-item" href="{{ route('endorsements.visiting') }}">Visiting</a>
                </div>
            </div>
        </li>



        @if (\Auth::user()->isModeratorOrAbove())
            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            {{-- Nav Item - Pages Collapse Menu --}}
            <li class="nav-item {{ Route::is('reports.trainings') || Route::is('reports.mentors') || Route::is('reports.access') ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                <i class="fas fa-fw fa-clipboard-list"></i>
                <span>Reports</span>
            </a>
            <div id="collapseTwo" class="collapse" data-bs-parent="#sidebar">
                <div class="bg-white py-2 collapse-inner rounded">

                @if(\Auth::user()->isAdmin())
                    <a class="collapse-item" href="{{ route('reports.trainings') }}">Trainings</a>
                @elseif(\Auth::user()->isModerator())
                    <a class="collapse-item" href="{{ route('reports.training.area', \Auth::user()->groups()->where('group_id', 2)->get()->first()->pivot->area_id) }}">Trainings</a>
                @endif

                @if(\Auth::user()->isAdmin())
                    <a class="collapse-item" href="{{ route('reports.activities') }}">Activities</a>
                @elseif(\Auth::user()->isModerator())
                    <a class="collapse-item" href="{{ route('reports.activities.area', \Auth::user()->groups()->where('group_id', 2)->get()->first()->pivot->area_id) }}">Activities</a>
                @endif

                <a class="collapse-item" href="{{ route('reports.mentors') }}">Mentors</a>

                @can('viewAccessReport', \App\Models\ManagementReport::class)
                    <a class="collapse-item" href="{{ route('reports.access') }}">Access</a>
                @endcan

                <a class="collapse-item" href="{{ route('reports.feedback') }}">Feedback</a>

                </div>
            </div>
            </li>
        @endif

        @if (\Auth::user()->isModeratorOrAbove())

            {{-- Nav Item - Utilities Collapse Menu --}}
            <li class="nav-item {{ Route::is('admin.settings') || Route::is('vote.overview') || Route::is('admin.templates') || Route::is('admin.logs') ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Administration</span>
            </a>
            <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-bs-parent="#sidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                @if (\Auth::user()->isAdmin())
                    <a class="collapse-item" href="{{ route('admin.settings') }}">Settings</a>
                    <a class="collapse-item" href="{{ route('vote.overview') }}">Votes</a>
                    <a class="collapse-item" href="{{ route('admin.logs') }}">Logs</a>
                @endif

                @if (\Auth::user()->isModeratorOrAbove())
                    <a class="collapse-item" href="{{ route('admin.templates') }}">Notification templates</a>
                @endif
                @can('viewAny', App\Models\Position::class)
                    <a class="collapse-item" href="{{ route('positions.index') }}">Positions</a>
                @endcan
                </div>
            </div>
            </li>

        @endif

        {{-- Divider --}}
        <div class="sidebar-divider d-none d-md-block"></div>

        @if(Config::get('app.env') != "production")
            <div class="alert alert-warning mt-2 fs-sm" role="alert">
                Development Env
            </div>
        @endif

        {{--  Logo and version element --}}
        <div class="d-flex flex-column align-items-center mt-auto mb-3">
            <a href="{{ Setting::get('linkHome') }}" class="d-block"><img class="logo" src="{{ asset('images/logos/'.Config::get('app.logo')) }}"></a>
            <a href="https://github.com/Vatsim-Scandinavia/controlcenter" target="_blank" class="version">Control Center v{{ config('app.version') }}</a>
        </div>

    </ul>

</nav>
