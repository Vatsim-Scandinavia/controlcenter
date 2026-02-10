<nav class="navbar navbar-expand bg-white topbar {{ (\Auth::user()->isModeratorOrAbove()) ? 'topbar-justify-moderator' : 'topbar-justify-user' }} mb-4 ps-4 pe-4 static-top shadow">

    <a class="sidebar-brand sidebar-brand-topbar align-items-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon">
            {!! file_get_contents(public_path('images/control-tower.svg')) !!}
        </div>

        <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>
    </a>

    {{-- Topbar Desktop Search --}}
    @if(\Auth::user()->isModeratorOrAbove())
        <form class="d-none d-md-inline-block my-2 my-md-0 mw-100 navbar-search" id="user-search-form-desktop">
            <div class="input-group">
                <div class="search input-group input-lg">
                    <div class="search-icon bg-light input-group-prepend">
                        <i class="fas fa-search fa-sm"></i>
                    </div>
                    <input class="user-search search-input form-control bg-light border-0 small" type="text" name="search" placeholder="Search for user">
                </div>

                <div class="search-spinner spinner-border spinner-border-sm" role="status"></div>
                <div class="search-results shadow-sm bg-light">

                </div>
            </div>
        </form>
    @endif

    {{-- Topbar Navbar --}}
    <ul class="navbar-nav">

        @if(\Auth::user()->isModeratorOrAbove())

            {{-- Search Dropdown (Visible Only XS) --}}
            <li class="nav-item dropdown no-arrow d-md-none">
                <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-search fa-fw"></i>
                </a>
                {{-- Dropdown - Messages --}}
                <div class="dropdown-menu dropdown-menu-end p-3 shadow" aria-labelledby="searchDropdown">
                    <form class="w-100 navbar-search" id="user-search-form-mobile">
                        <div class="search input-group input-lg">
                            <div class="search-icon bg-light input-group-prepend">
                                <i class="fas fa-search fa-sm"></i>
                            </div>
                            <input class="user-search search-input form-control bg-light border-0 small" type="text" name="search" placeholder="Search for user">
                        </div>

                        <div class="search-spinner spinner-border spinner-border-sm" role="status"></div>
                        <div class="search-results bg-light">

                        </div>
                    </form>
                </div>
            </li>

        @endif

        <div class="topbar-divider d-none d-lg-block"></div>

        {{-- Nav Item - User Information --}}
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="me-2 d-none d-lg-inline small">{{ Auth::user()->name }}</span>
                <i class="fas fa-user"></i>
            </a>
            {{-- Dropdown - User Information --}}
            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('user.show', Auth::user()->id) }}">
                    <i class="fas fa-user fa-sm fa-fw me-2 text-primary"></i>
                    My details
                </a>
                <a class="dropdown-item" href="{{ route('user.reports', Auth::user()->id) }}">
                    <i class="fas fa-file fa-sm fa-fw me-2 text-primary"></i>
                    My reports
                </a>
                <a class="dropdown-item" href="{{ route('user.settings') }}">
                    <i class="fas fa-cogs fa-sm fa-fw me-2 text-primary"></i>
                    Settings
                </a>
                <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-primary"></i>
                    Logout
                </a>
            </div>
        </li>

        <li class="nav-item dropdown d-lg-none">
            <button class="nav-link position-relative" id="sidebar-button">
                <i class="fas fa-bars"></i>
                @if(\Auth::user()->tasks->where('status', \App\Helpers\TaskStatus::PENDING)->count())
                    <span class="position-absolute top-40 start-75 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                @endif
            </button>
        </li>

    </ul>

</nav>
