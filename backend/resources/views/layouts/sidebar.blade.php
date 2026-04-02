<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="{{ asset('public/assets/dist/img/user1-128x128.jpg') }}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Brad Diesel
                  <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Call me whenever you can...</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="{{ asset('public/assets/dist/img/user8-128x128.jpg') }}" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  John Pierce
                  <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">I got your message bro</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="{{ asset('public/assets/dist/img/user3-128x128.jpg') }}" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Nora Silvester
                  <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">The subject goes here</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
        </div>
      </li>
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">15</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">15 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> 4 new messages
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-users mr-2"></i> 8 friend requests
            <span class="float-right text-muted text-sm">12 hours</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> 3 new reports
            <span class="float-right text-muted text-sm">2 days</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
    </ul>
</nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('home') }}" class="brand-link">
      <img src="{{ asset('public/assets/dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">AdminLTE 3</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('public/assets/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">Alexander Pierce</a>
            </div>
        </div>

      <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                    <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                with font-awesome or any other icon font library -->
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->is('home') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          {{ __('Dashboard') }}
                        </p>
                    </a>
                </li>
                @can('service-list')
                  <li class="nav-item">
                    <a href="{{ route('service.index') }}" class="nav-link {{ request()->is('service') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                        <p>
                          {{ __('Service') }}
                        </p>
                    </a>
                  </li>
                @endcan
                @can('project-list')
                  <li class="nav-item {{ request()->is('project') || request()->is('team') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('project') || request()->is('team') ? 'active' : '' }}">
                      <i class="nav-icon fas fa-tachometer-alt"></i>
                      <p>
                        {{ __('Projects') }}
                        <i class="right fas fa-angle-left"></i>
                      </p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="{{ route('project.index') }}" class="nav-link {{ request()->is('project') ? 'active' : '' }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>{{ __('Projects List') }}</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="{{ route('team') }}" class="nav-link {{ request()->is('team') ? 'active' : '' }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>{{ __('Team List') }}</p>
                        </a>
                      </li>
                    </ul>
                  </li>
                  {{-- <li class="nav-item">
                    <a href="{{ route('project.index') }}" class="nav-link {{ request()->is('project') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                        <p>
                          {{ __('Projects') }}
                        </p>
                    </a>
                  </li> --}}
                @endcan
                @can('task-list')
                  <li class="nav-item {{ request()->is('task') || request()->is('subTask') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('task') || request()->is('subTask') ? 'active' : '' }}">
                      <i class="nav-icon fas fa-tachometer-alt"></i>
                      <p>
                        {{ __('Tasks') }}
                        <i class="right fas fa-angle-left"></i>
                      </p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="{{ route('task.index') }}" class="nav-link {{ request()->is('task') ? 'active' : '' }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>{{ __('Task List') }}</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="{{ route('subTask') }}" class="nav-link {{ request()->is('subTask') ? 'active' : '' }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>{{ __('Sub Task List') }}</p>
                        </a>
                      </li>
                    </ul>
                  </li>
                  {{-- <li class="nav-item">
                    <a href="{{ route('task.index') }}" class="nav-link {{ request()->is('task') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                        <p>
                          {{ __('Tasks') }}
                        </p>
                    </a>
                  </li> --}}
                @endcan
                @can('company-list')
                  <li class="nav-item">
                    <a href="{{ route('company.index') }}" class="nav-link {{ request()->is('company') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                        <p>
                          {{ __('Companies') }}
                        </p>
                    </a>
                  </li>
                @endcan
                @can('user-list')
                  <li class="nav-item">
                      <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users') ? 'active' : '' }}">
                          <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                          <p>
                            {{ __('Users') }}
                          </p>
                      </a>
                  </li>
                @endcan
                @can('note-list')
                  <li class="nav-item">
                      <a href="{{ route('note.index') }}" class="nav-link {{ request()->is('note') ? 'active' : '' }}">
                          <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                          <p>
                            {{ __('Notes') }}
                          </p>
                      </a>
                  </li>
                @endcan
                @can('role-list')
                  <li class="nav-item">
                      <a href="{{ route('roles.index') }}" class="nav-link {{ request()->is('roles') ? 'active' : '' }}">
                          <i class="nav-icon fa fa-user" aria-hidden="true"></i>
                          <p>
                            {{ __('Role') }}
                          </p>
                      </a>
                  </li>
                @endcan
                <li class="nav-item">
                    <a href="{{ route('logout') }}" class="nav-link" onclick="event.preventDefault();

                        document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          {{ __('Logout') }}
                        </p>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">

                      @csrf

                  </form>
                </li>
                {{-- <li class="nav-item">
                    <a href="{{ route('superadmintask') }}" class="nav-link {{ request()->is('superadmintask') || request()->is('superadmintask/createtask') || request()->is('superadmintask/edittask/*') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-tasks" aria-hidden="true"></i>
                        <p>
                            Tasks
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('superadminclient') }}" class="nav-link {{ request()->is('superadminclient') || request()->is('superadminclient/createclient') || request()->is('superadminclient/editclient/*') ? 'active' : '' }}">
                      <i class="nav-icon fa fa-archive" aria-hidden="true"></i>
                      <p>
                          Client
                      </p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('superadminservice') }}" class="nav-link {{ request()->is('superadminservice') || request()->is('superadminservice/createservice') || request()->is('superadminservice/editservice/*') ? 'active' : '' }}">
                      <i class="nav-icon fa fa-archive" aria-hidden="true"></i>
                      <p>
                          Service
                      </p>
                  </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('superadminprojects') }}" class="nav-link {{ request()->is('superadminprojects') || request()->is('superadminprojects/createprojects') || request()->is('superadminprojects/viewprojects/*') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-archive" aria-hidden="true"></i>
                        <p>
                            Projects
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-comments" aria-hidden="true"></i>
                        <p>
                            Discussion
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-folder" aria-hidden="true"></i>
                        <p>
                            Documents
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('superadminnote') }}" class="nav-link {{ request()->is('superadminnote') || request()->is('superadminnote/createnote') || request()->is('superadminnote/editnote/*') ? 'active' : '' }}">
                        <i class="nav-icon fa fa-sticky-note" aria-hidden="true"></i>
                        <p>
                            Notes
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-file" aria-hidden="true"></i>
                        <p>
                            Reports
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('allsuperuser') }}" class="nav-link">
                        <i class="nav-icon fa fa-users" aria-hidden="true"></i>
                        <p>
                            User
                        </p>
                    </a>
                </li> --}}
            </ul>
        </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
