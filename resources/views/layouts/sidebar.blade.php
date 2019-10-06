        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-sleigh"></i>
                </div>
                  
            <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
            <a class="nav-link" href="index.html">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span></a>
            </li>      
    
            <li class="nav-item">
            <a class="nav-link" href="index.html">
                <i class="fas fa-fw fa-calendar"></i>
                <span>Vatbook</span></a>
            </li>
    
            <li class="nav-item">
            <a class="nav-link" href="index.html">
                <i class="fas fa-fw fa-book"></i>
                <span>Training Content</span></a>
            </li>
    
            <!-- Divider -->
            <hr class="sidebar-divider">
    
            <!-- Heading -->
            <div class="sidebar-heading">
            Training
            </div>
    
            <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-chalkboard-teacher"></i>
                <span>My students</span></a>
            </li>
    
            <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-calendar-alt"></i>
                <span>Sweatbox Calendar</span></a>
            </li>
    
            <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-flag"></i>
                <span>Requests</span></a>
            </li>
    
            <!-- Divider -->
            <hr class="sidebar-divider">
    
            <!-- Heading -->
            <div class="sidebar-heading">
            Members
            </div>
    
            <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-users"></i>
                <span>Overview</span></a>
            </li>
    
            <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-check-square"></i>
                <span>Endorsements</span></a>
            </li>
    
            <!-- Divider -->
            <hr class="sidebar-divider">
    
            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                <i class="fas fa-fw fa-clipboard-list"></i>
                <span>Reports</span>
            </a>
            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="#">Statistics</a>
                <a class="collapse-item" href="#">Mentors</a>
                <a class="collapse-item" href="#">ATC Activity</a>
                </div>
            </div>
            </li>
    
            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Administration</span>
            </a>
            <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="#">Settings</a>
                <a class="collapse-item" href="#">Training content</a>
                <a class="collapse-item" href="#">Notification templates</a>
                </div>
            </div>
            </li>            

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->