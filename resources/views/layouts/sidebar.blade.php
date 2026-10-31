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
            @php
                $pendingTaskCount = \Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count();
            @endphp

            <x-sidebar.item :href="route('tasks')" icon="fa-list" title="Tasks" :active="Route::is('tasks')">
                @if($pendingTaskCount)
                    <span class="badge text-bg-danger">{{ $pendingTaskCount }}</span>
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

        @canany(['training.mentor-dashboard.view', 'bookings.sweatbox.use', 'fir.management.reports.view'])

            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            {{-- Heading --}}
            <div class="sidebar-heading">
            Training
            </div>

            @can('training.mentor-dashboard.view')
                <x-sidebar.item :href="route('mentor')" icon="fa-chalkboard-teacher" title="My students" :active="Route::is('mentor')" />
            @endcan

            @can('bookings.sweatbox.use')
                <x-sidebar.item :href="route('sweatbook')" icon="fa-calendar-alt" title="Sweatbox Calendar" :active="Route::is('sweatbook')" />
            @endcan

            @can('fir.management.reports.view')

                {{-- Nav Item - Pages Collapse Menu --}}
                <x-sidebar.section icon="fa-flag" title="Requests" :active="Route::is('requests') || Route::is('requests.history')" id="collapseReq">
                    <x-sidebar.item :href="route('requests')" title="Open Requests" collapse />
                    <x-sidebar.item :href="route('requests.history')" title="Closed Requests" collapse />
                </x-sidebar.section>
            @endcan

        @endcanany

        {{-- Divider --}}
        <div class="sidebar-divider"></div>

        {{-- Heading --}}
        <div class="sidebar-heading">
        Members
        </div>

        @can('users.manage')

            {{-- Nav Item - Pages Collapse Menu --}}
            <x-sidebar.section icon="fa-users" title="Users" :active="Route::is('users') || Route::is('users.other')" id="collapseMem">
                <x-sidebar.item :href="route('users')" title="Member Overview" collapse />
                <x-sidebar.item :href="route('users.other')" title="Other Users" collapse />
            </x-sidebar.section>

        @endif

        {{-- Nav Item - Pages Collapse Menu --}}
        @php
            $areas = \App\Models\Area::all();
        @endphp

        @if($areas->count() > 1)
            <x-sidebar.section icon="fa-address-book" title="ATC Roster" :active="Route::is('roster')" id="collapseRosters">
                @foreach($areas as $area)
                    <x-sidebar.item :href="route('roster', $area->id)" :title="$area->name" collapse />
                @endforeach
            </x-sidebar.section>
        @else
            <x-sidebar.item :href="route('roster', $areas->first()->id)" icon="fa-address-book" title="ATC Roster" :active="Route::is('roster')" />
        @endif

        {{-- Nav Item - Pages Collapse Menu --}}
        <x-sidebar.section icon="fa-check-square" title="Endorsements" :active="Route::is('endorsements.*')" id="collapseEndorsements">
            <x-sidebar.item :href="route('endorsements.solos')" title="Solo" collapse />
            <x-sidebar.item :href="route('endorsements.examiners')" title="Examiner" collapse />
            <x-sidebar.item :href="route('endorsements.visiting')" title="Visiting" collapse />
        </x-sidebar.section>



        @can('fir.management.reports.view')
            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            {{-- Nav Item - Pages Collapse Menu --}}
            <x-sidebar.section icon="fa-clipboard-list" title="Reports" :active="Route::is('reports.trainings') || Route::is('reports.training.area') || Route::is('reports.activities') || Route::is('reports.activities.area') || Route::is('reports.mentors') || Route::is('reports.access') || Route::is('reports.feedback')" id="collapseTwo">

                @can('training.statistics.view')
                    <x-sidebar.item :href="route('reports.trainings')" title="Training Statistics" collapse />
                @endcan
                @can('training.activities.view')
                    <x-sidebar.item :href="route('reports.activities')" title="Training Activities" collapse />
                @endcan

                <x-sidebar.item :href="route('reports.mentors')" title="Mentors" collapse />

                @can('viewAccessReport', \App\Models\ManagementReport::class)
                    <x-sidebar.item :href="route('reports.access')" title="Access" collapse />
                @endcan

                <x-sidebar.item :href="route('reports.feedback')" title="Feedback" collapse />

            </x-sidebar.section>
        @endif

        @if(auth()->user()->canAny(['system.health.view', 'users.manage']) || auth()->user()->can('viewAny', App\Models\Position::class))

            {{-- Nav Item - Utilities Collapse Menu --}}
            <x-sidebar.section icon="fa-cogs" title="Administration" :active="Route::is('admin.*') || Route::is('positions.*') || Route::is('vote.overview')" id="collapseUtilities">
                @can('system.health.view')
                    <x-sidebar.item :href="route('admin.settings')" title="Settings" collapse />
                    <x-sidebar.item :href="route('vote.overview')" title="Votes" collapse />
                    <x-sidebar.item :href="route('admin.logs')" title="Logs" collapse />
                @endcan

                @can('users.manage')
                    <x-sidebar.item :href="route('admin.templates')" title="Notification templates" collapse />
                @endcan
                @can('viewAny', App\Models\Position::class)
                    <x-sidebar.item :href="route('positions.index')" title="Positions" collapse />
                @endcan
            </x-sidebar.section>

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
