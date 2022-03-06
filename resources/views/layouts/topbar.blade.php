<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Search -->
    @if(\Auth::user()->isMentorOrAbove())
        <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" id="user-search-form-desktop">
            <div class="input-group">
                <div class="search input-group input-lg">
                    <div class="search-icon bg-light input-group-prepend">
                        <i class="fas fa-search fa-sm"></i>
                    </div>
                    <input class="search-input form-control bg-light border-0 small" type="text" name="search" placeholder="Search for user">
                </div>

                <div class="search-spinner spinner-border spinner-border-sm" role="status"></div>
                <div class="search-results shadow-sm bg-light">
                    
                </div>
            </div>
        </form>
    @endif

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        @if(\Auth::user()->isMentorOrAbove())
            <!-- Nav Item - Search Dropdown (Visible Only XS) -->
            <li class="nav-item dropdown no-arrow d-sm-none">
                <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-search fa-fw"></i>
                </a>
                <!-- Dropdown - Messages -->
                <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                    <form class="form-inline mr-auto w-100 navbar-search" id="user-search-form-mobile">
                        <div class="search input-group input-lg">
                            <div class="search-icon bg-light input-group-prepend">
                                <i class="fas fa-search fa-sm"></i>
                            </div>
                            <input class="search-input form-control bg-light border-0 small" type="text" name="search" placeholder="Search for user">
                        </div>

                        <div class="search-spinner spinner-border spinner-border-sm" role="status"></div>
                        <div class="search-results bg-light">
                
                        </div>
                    </form>
                </div>
            </li>
        @endif

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name }}</span>
            <i class="fas fa-user"></i>
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="{{ route('user.show', Auth::user()->id) }}">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                My details
            </a>
            <a class="dropdown-item" href="{{ route('user.settings') }}">
                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                Settings
            </a>
            <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('logout') }}">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Logout
            </a>
        </div>
        </li>

    </ul>

</nav>