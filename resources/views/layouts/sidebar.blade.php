<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon">
            <i class="far fa-radar"></i>
        </div>

    <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>
    </a>

    @auth
        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <li class="nav-item {{ Route::is('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
        </li>

        <li class="nav-item {{ Route::is('vatbook') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('vatbook') }}">
            <i class="fas fa-fw fa-calendar"></i>
            <span>Vatbook</span></a>
        </li>

        @if (\Auth::user()->isMentor())

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
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
            <span>Sweatbox Calendar</span></a>
        </li>

        @endif
        @if (\Auth::user()->isModerator())

        <li class="nav-item {{ Route::is('requests') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('requests') }}">
            <i class="fas fa-fw fa-flag"></i>
            <span>Requests</span></a>
        </li>

        @endif

        @if (\Auth::user()->isMentor())
        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
        Members
        </div>

        <li class="nav-item {{ Route::is('users') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('users') }}">
            <i class="fas fa-fw fa-users"></i>
            <span>Overview</span></a>
        </li>

        <li class="nav-item {{ Route::is('users.endorsements') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('users.endorsements') }}">
            <i class="fas fa-fw fa-check-square"></i>
            <span>Solo Endorsements</span></a>
        </li>

        @endif

        @if (\Auth::user()->isAdmin())

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Nav Item - Pages Collapse Menu -->
        <li class="nav-item {{ Route::is('reports.trainings') || Route::is('reports.mentors') || Route::is('reports.atc') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-fw fa-clipboard-list"></i>
            <span>Reports</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="{{ route('reports.trainings') }}">Trainings</a>
            <a class="collapse-item" href="{{ route('reports.mentors') }}">Mentors</a>
            <a class="collapse-item" href="{{ route('reports.atc') }}">ATC Activity</a>
            </div>
        </div>
        </li>

        <!-- Nav Item - Utilities Collapse Menu -->
        <li class="nav-item {{ Route::is('admin.settings') || Route::is('vote.overview') || Route::is('admin.templates') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Administration</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="{{ route('admin.settings') }}">Settings</a>
            <a class="collapse-item" href="{{ route('vote.overview') }}">Votes</a>
            <a class="collapse-item" href="{{ route('admin.templates') }}">Notification templates</a>
            </div>
        </div>
        </li>

        @endif

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

        <!-- Logo -->
        <img class="logo" src="{{ asset('images/vatsca-logo-negative.svg') }}">
    @else
        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <li class="nav-item active">
        <a class="nav-link" href="{{ route('login') }}">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login</span></a>
        </li>
    @endauth

</ul>
<!-- End of Sidebar -->
