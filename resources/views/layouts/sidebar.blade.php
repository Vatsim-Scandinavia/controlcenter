<nav>

    <ul class="navbar-nav sidebar" id="sidebar">

        {{-- Sidebar - Brand --}}
        <a class="sidebar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <div class="sidebar-brand-icon">
                {!! file_get_contents(public_path('images/control-tower.svg')) !!}
            </div>

            <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>

            <button type="button" id="sidebar-button-close" class="sidebar-button-close ms-auto">
                <i class="fas fa-times"></i>
            </button>
        </a>

        {{-- Divider --}}
        <div class="sidebar-divider my-0"></div>

        <x-sidebar.item :href="route('dashboard')" icon="fa-table-columns" title="Dashboard" :active="Route::is('dashboard')" />

        @can('update', [\App\Models\Task::class])
            <x-sidebar.item :href="route('tasks')" icon="fa-list" title="Tasks" :active="Route::is('tasks')">
                @if(\Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count())
                    <span class="badge text-bg-danger">{{ \Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count() }}</span>
                @endif
            </x-sidebar.item>
        @endcan

        @can('view', \App\Models\Booking::class)
            <x-sidebar.item :href="route('booking')" icon="fa-calendar" title="Booking" :active="Route::is('booking*')" />
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

            <x-sidebar.item :href="route('mentor')" icon="fa-chalkboard-teacher" title="My students" :active="Route::is('mentor')" />

            @can('view', \App\Models\Sweatbook::class)
                <x-sidebar.item :href="route('sweatbook')" icon="fa-calendar-alt" title="Sweatbox Calendar" :active="Route::is('sweatbook')" />
            @endcan

        @endif
        @if (\Auth::user()->isModeratorOrAbove())

            {{-- Nav Item - Pages Collapse Menu --}}
            <x-sidebar.section icon="fa-flag" title="Requests" :active="Route::is('requests') || Route::is('requests.history')" id="collapseReq">
                @can('viewActiveRequests', \App\Models\Training::class)
                    <a class="collapse-item" href="{{ route('requests') }}">Open Requests</a>
                @endcan
                @can('viewHistoricRequests', \App\Models\Training::class)
                    <a class="collapse-item" href="{{ route('requests.history') }}">Closed Requests</a>
                @endcan
            </x-sidebar.section>

            <x-sidebar.section icon="fa-chart-line" title="Reports" :active="Route::is('reports.trainings') || Route::is('reports.mentors') || Route::is('reports.activities')" id="collapseTrainingReports">
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

                @can('viewMentors', \App\Models\ManagementReport::class)
                    <a class="collapse-item" href="{{ route('reports.mentors') }}">Mentors</a>
                @endcan
            </x-sidebar.section>

        @endif

        {{-- Divider --}}
        <div class="sidebar-divider"></div>

        {{-- Heading --}}
        <div class="sidebar-heading">
        Division
        </div>

        @if (\Auth::user()->isModeratorOrAbove())

            {{-- Nav Item - Pages Collapse Menu --}}
            @can('index', \App\Models\User::class)
                <x-sidebar.section icon="fa-users" title="Users" :active="Route::is('users') || Route::is('users.other')" id="collapseMem">
                    <a class="collapse-item" href="{{ route('users') }}">Member Overview</a>
                    <a class="collapse-item" href="{{ route('users.other') }}">Other Users</a>
                </x-sidebar.section>
            @endcan

        @endif

        {{-- Nav Item - Pages Collapse Menu --}}
        @php
            $areas = \App\Models\Area::all();
        @endphp

        @if($areas->count() > 1)
            <x-sidebar.section icon="fa-address-book" title="ATC Roster" :active="Route::is('roster')" id="collapseRosters">
                @foreach($areas as $area)
                    <a class="collapse-item" href="{{ route('roster', $area->id) }}">{{ $area->name }}</a>
                @endforeach
            </x-sidebar.section>
        @else
            <x-sidebar.item :href="route('roster', $areas->first()->id)" icon="fa-address-book" title="ATC Roster" :active="Route::is('roster')" />
        @endif

        {{-- Nav Item - Pages Collapse Menu --}}
        <x-sidebar.section icon="fa-check-square" title="Endorsements" :active="Route::is('endorsements.*')" id="collapseEndorsements">
            <a class="collapse-item" href="{{ route('endorsements.solos') }}">Solo</a>
            <a class="collapse-item" href="{{ route('endorsements.examiners') }}">Examiner</a>
            <a class="collapse-item" href="{{ route('endorsements.visiting') }}">Visiting</a>
        </x-sidebar.section>

        @if (\Auth::user()->isModeratorOrAbove())
            <x-sidebar.section icon="fa-chart-pie" title="Reports" :active="Route::is('reports.access') || Route::is('reports.feedback')" id="collapseDivisionReports">
                @can('viewAccessReport', \App\Models\ManagementReport::class)
                    <a class="collapse-item" href="{{ route('reports.access') }}">Access</a>
                @endcan
                @can('viewFeedback', \App\Models\ManagementReport::class)
                    <a class="collapse-item" href="{{ route('reports.feedback') }}">Feedback</a>
                @endcan
            </x-sidebar.section>
        @endif


        @if (\Auth::user()->isModeratorOrAbove())
            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            <div class="sidebar-heading">
                Administration
            </div>

            <x-sidebar.section icon="fa-cogs" title="Settings" :active="Route::is('admin.settings') || Route::is('admin.templates')" id="collapseSettings">
                @if (\Auth::user()->isAdmin())
                    <a class="collapse-item" href="{{ route('admin.settings') }}">Settings</a>
                @endif
                @can('viewTemplates', \Illuminate\Notifications\Notification::class)
                    <a class="collapse-item" href="{{ route('admin.templates') }}">Templates</a>
                @endcan
            </x-sidebar.section>

            @if (\Auth::user()->isAdmin())
                @can('index', \App\Models\Vote::class)
                    <x-sidebar.item :href="route('vote.overview')" icon="fa-vote-yea" title="Votes" :active="Route::is('vote.overview')" />
                @endcan
                @can('index', \App\Models\ActivityLog::class)
                    <x-sidebar.item :href="route('admin.logs')" icon="fa-history" title="Logs" :active="Route::is('admin.logs')" />
                @endcan
            @endif

            @can('viewAny', App\Models\Position::class)
                <x-sidebar.section icon="fa-database" title="Navigational Data" :active="Route::is('positions.index')" id="collapseNavData">
                    <a class="collapse-item" href="{{ route('positions.index') }}">Positions</a>
                </x-sidebar.section>
            @endcan

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
