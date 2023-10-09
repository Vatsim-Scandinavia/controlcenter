<nav class="navbar navbar-expand bg-white topbar {{ (\Auth::user()->isModeratorOrAbove()) ? 'topbar-justify-moderator' : 'topbar-justify-user' }} mb-4 ps-4 pe-4 static-top shadow">

    <a class="sidebar-brand sidebar-brand-topbar align-items-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon">
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 448 512" xml:space="preserve">
                <path d="M160,24c0-13.2,10.7-24,24-24h80c13.3,0,24,10.8,24,24s-10.7,24-24,24h-16v48h40c17.7,0,32,14.3,32,32h93.2
                c21.5,0,36.9,20.7,30.7,41.2l-41.2,137.3c7.9,3.9,13.3,12.1,13.3,21.5c0,13.3-10.7,24-24,24h-24v136c0,13.3-10.7,24-24,24
                s-24-10.7-24-24V352H128v136c0,13.3-10.7,24-24,24c-13.2,0-24-10.7-24-24V352H56c-13.2,0-24-10.7-24-24c0-9.4,5.4-17.6,13.3-21.5
                L4.1,169.2c-6.2-20.5,9.2-41.2,30.6-41.2H128c0-17.7,14.3-32,32-32h40V48h-16C170.7,48,160,37.2,160,24L160,24z M128,304V176H56.3
                l38.4,128H128z M176,304h96V176h-96V304z M320,176v128h33.3l38.4-128H320z"/>
            </svg>
        </div>

        <div class="sidebar-brand-text mx-3 text-primary">{{ config('app.name') }}</div>
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