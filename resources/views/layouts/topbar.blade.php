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

        <!-- Nav Item - Alerts -->
        <li class="nav-item dropdown no-arrow mx-1">
        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell fa-fw"></i>
            <!-- Counter - Alerts -->
            <!--<span class="badge badge-danger badge-counter">3+</span>-->
        </a>
        <!-- Dropdown - Alerts -->
        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
            <h6 class="dropdown-header">
            Alerts
            </h6>

            <a class="dropdown-item d-flex align-items-center" href="#">
                <div class="mr-3">
                    <div class="icon-circle bg-warning">
                    <i class="fas fa-exclamation text-white"></i>
                    </div>
                </div>
                <div>
                    Alerts will be implemented later. Please check your e-mail for alerts.
                </div>
            </a>
            <!--
            <a class="dropdown-item d-flex align-items-center" href="#">
                <div class="mr-3">
                    <div class="icon-circle bg-success">
                    <i class="fas fa-check text-white"></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">December 12, 2019</div>
                    Your student Name Nameson has passed their S3 ATSimTest
                </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="#">
                <div class="mr-3">
                    <div class="icon-circle bg-warning">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">December 2, 2019</div>
                    Your student Name Nameson has not had training in over two months.
                </div>
            </a>
            <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>-->
        </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name }}</span>
            <i class="fas fa-user"></i>
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
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