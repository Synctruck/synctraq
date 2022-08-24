<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li >
            <div id="google_translate_element" class="google"></div>
        </li>
        @if(Session::has('user'))
            @if(Session::get('user')->role->name == 'Administrador')
                <li class="nav-heading"></li>
                <li >
                    <a class="nav-link {{Request::is('dashboard') ? 'active' : 'collapsed'}}" href="{{url('dashboard')}}">
                        <i class="bx bxs-dashboard"></i>
                        <span>DASHBOARD</span>
                    </a>
                </li>
            @endif
            @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'View')
                <li >
                    <a class="nav-link {{Request::is('package-manifest') ? 'active' : 'collapsed'}}" href="{{url('package-manifest')}}">
                        <i class="bx bxs-box"></i>
                        <span>MANIFEST</span>
                    </a>
                </li>
            @endif

            @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Team' || Session::get('user')->role->name == 'Driver' || Session::get('user')->role->name == 'Validador')
                {{-- <li class="nav-heading">* PROCESSES</li> --}}
                @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Validador')
                    <li >
                        <a class="nav-link {{Request::is('package-inbound') ? 'show' : 'collapsed'}}" href="{{url('/package-inbound')}}">
                            <i class="bx bx-barcode-reader"></i>
                            <span>INBOUND</span>
                        </a>
                    </li>
                @endif

                @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Team')
                    @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->permissionDispatch)
                        <li >
                            <a class="nav-link {{Request::is('package-dispatch') ? 'show' : 'collapsed'}}" href="{{url('/package-dispatch')}}">
                                <i class="bx bx-car"></i>
                                <span>DISPATCH</span>
                            </a>
                        </li>
                    @endif
                @endif

                @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Team')
                    @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->permissionDispatch)
                        <li >
                            <a class="nav-link {{Request::is('package-check') ? 'show' : 'collapsed'}}" href="{{url('/package-check')}}">
                                <i class="bx bx-barcode-reader"></i>
                                <span>CHECK STOP</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- @if(Session::get('user')->role->name == 'Administrador')
                    <li >
                        <a class="nav-link {{Request::is('assigned') ? 'show' : 'collapsed'}}" href="{{url('/assigned')}}">
                            <i class="bx bx-user"></i>
                            <span>Assigned Team</span>
                        </a>
                    </li>
                @endif --}}
                @if(Session::get('user')->role->name == 'Administrador')
                    <li style="display: none;">
                        <a class="nav-link {{Request::is('package-delivery') ? 'show' : 'collapsed'}}" href="{{url('package-delivery')}}">
                            <i class="bx bx-car"></i>
                            <span>DELIVERIES</span>
                        </a>
                    </li>
                @endif


                @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Team')
                    {{-- <li class="nav-heading">* DESELECT</li> --}}
                    {{-- @if(Session::get('user')->role->name == 'Administrador')
                        <li >
                            <a class="nav-link {{Request::is('package-not-exists') ? 'show' : 'collapsed'}}" href="{{url('/package-not-exists')}}">
                                <i class="bx bx-barcode-reader"></i>
                                <span>Not Exists</span>
                            </a>
                        </li>
                    @endif --}}

                    @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->permissionDispatch)
                        <li >
                            <a class="nav-link {{Request::is('package/return') ? 'show' : 'collapsed'}}" href="{{url('/package/return')}}">
                                <i class="bx bx-car"></i>
                                <span>RE-INBOUND</span>
                            </a>
                        </li>
                    @endif

                    {{-- @if(Session::get('user')->role->name == 'Administrador')
                        <li >
                            <a class="nav-link {{Request::is('unassigned') ? 'show' : 'collapsed'}}" href="{{url('/unassigned')}}">
                                <i class="bx bx-user"></i>
                                <span>Unssigned Team</span>
                            </a>
                        </li>
                    @endif --}}
                @endif

                @if(Session::get('user')->role->name == 'Team')
                    <li >
                        <a class="nav-link {{Request::is('assignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/assignedTeam')}}">
                            <i class="bx bx-user"></i>
                            <span>Assigned</span>
                        </a>
                    </li>
                    <li >
                        <a class="nav-link {{Request::is('unassignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/unassignedTeam')}}">
                            <i class="bx bx-user"></i>
                            <span>Unssigned</span>
                        </a>
                    </li>
                @endif
            @endif

            @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'View' || Session::get('user')->role->name == 'Team')
                <li class="nav-heading">Reports</li>
                <li class="nav-item">
                    <a class="nav-link
                            {{
                                Request::is('report/failed') ||
                                Request::is('report/assigns') ||
                                Request::is('report/dispatch') ||
                                Request::is('report/delivery') ||
                                Request::is('report/inbound') ||
                                Request::is('report/manifest') ||
                                Request::is('report/notExists') ||
                                Request::is('report/return-company')
                                ?
                                    ''
                                :
                                    'collapsed'
                            }}" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#"
                    >
                        <i class="bi bi-journal-text"></i><span>GENERAL REPORTS</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="forms-nav" class="nav-content
                            {{
                                Request::is('report/failed') ||
                                Request::is('report/assigns') ||
                                Request::is('report/dispatch') ||
                                Request::is('report/delivery') ||
                                Request::is('report/inbound') ||
                                Request::is('report/manifest') ||
                                Request::is('report/notExists') ||
                                Request::is('report/return-company')
                                ?
                                    'collapse show'
                                :
                                    'collapse'
                            }}" data-bs-parent="#sidebar-nav"
                    >
                        <li>
                            <a class="nav-link {{Request::is('report/manifest') ? 'show' : 'collapsed'}}" href="{{url('/report/manifest')}}">
                                <i class="bx bxs-report"></i>
                                <span>Manifest</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{Request::is('report/inbound') ? 'show' : 'collapsed'}}" href="{{url('/report/inbound')}}">
                                <i class="bx bxs-report"></i>
                                <span>Inbound</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{Request::is('report/dispatch') ? 'show' : 'collapsed'}}" href="{{url('/report/dispatch')}}">
                                <i class="bx bxs-report"></i>
                                <span>Dispatch</span>
                            </a>
                        </li>
                        {{-- <li>
                            <a class="nav-link {{Request::is('report/failed') ? 'show' : 'collapsed'}}" href="{{url('/report/failed')}}">
                                <i class="bx bxs-report"></i>
                                <span>Failed</span>
                            </a>
                        </li> --}}
                        <li>
                            <a class="nav-link {{Request::is('report/delivery') ? 'show' : 'collapsed'}}" href="{{url('/report/delivery')}}">
                                <i class="bx bxs-report"></i>
                                <span>Delivery</span>
                            </a>
                        </li>
                        {{-- <li>
                            <a class="nav-link {{Request::is('report/assigns') ? 'show' : 'collapsed'}}" href="{{url('/report/assigns')}}">
                                <i class="bx bxs-report"></i>
                                <span>Assigns Teams</span>
                            </a>
                        </li> --}}
                        <li>
                            <a class="nav-link {{Request::is('report/notExists') ? 'show' : 'collapsed'}}" href="{{url('/report/notExists')}}">
                                <i class="bx bxs-report"></i>
                                <span>Not Exists</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{Request::is('report/return-company') ? 'show' : 'collapsed'}}" href="{{url('/report/return-company')}}">
                                <i class="bx bxs-report"></i>
                                <span>Return Company</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(Session::get('user')->role->name == 'Administrador' || Session::get('user')->role->name == 'Team')

                <li class="nav-heading">Maintenances</li>
                <li class="nav-item">
                    <a class="nav-link {{ (Request::is('user') || Request::is('team') || Request::is('driver') || Request::is('validator')   || Request::is('viewer') ) ? '' : 'collapsed'}}" data-bs-target="#maintenances-nav" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('team') ? 'true' : 'false'}}">
                      <i class="bi bi-person"></i><span>USERS GENERAL</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="maintenances-nav" class="nav-content collapse {{Request::is('user') || Request::is('team') || Request::is('driver') || Request::is('validator')   || Request::is('viewer')? 'show' : ''}}" data-bs-parent="#sidebar-nav" style="">
                        <li >
                            <a class="nav-link {{Request::is('user') ? 'show' : 'collapsed'}}" href="{{url('user')}}">
                                <i class="bi bi-person"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li >
                            <a class="nav-link {{Request::is('team') ? 'active' : 'collapsed'}}" href="{{url('team')}}">
                                <i class="bi bi-person"></i>
                                <span>Teams</span>
                            </a>
                        </li>
                        <li >
                            <a class="nav-link {{Request::is('driver') ? 'active' : 'collapsed'}}" href="{{url('driver')}}">
                                <i class="bi bi-person"></i>
                                <span>Drivers</span>
                            </a>
                        </li>
                        <li >
                            <a class="nav-link {{Request::is('viewer') ? 'active' : 'collapsed'}}" href="{{url('viewer')}}">
                                <i class="bi bi-person"></i>
                                <span>Viewers</span>
                            </a>
                        </li>
                        <li >
                            <a class="nav-link {{Request::is('validator') ? 'active' : 'collapsed'}}" href="{{url('validator')}}">
                                <i class="bi bi-person"></i>
                                <span>Validators</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ (Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan')) ? '' : 'collapsed'}}" data-bs-target="#configuration-nav" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan') ? 'true' : 'false'}}">
                      <i class="bi bi-person"></i><span>CONFIGURATION GENERAL</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="configuration-nav" class="nav-content collapse {{(Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan'))? 'show' : ''}}" data-bs-parent="#sidebar-nav" style="">
                        @if(Session::get('user')->role->name == 'Administrador')
                        <li >
                            <a class="nav-link {{Request::is('routes') ? 'show' : 'collapsed'}}" href="{{url('routes')}}">
                                <i class="bx bx-command"></i>
                                <span>Routes</span>
                            </a>
                        </li>
                        @endif
                        <li >
                            <a class="nav-link {{Request::is('comments') ? 'active' : 'collapsed'}}" href="{{url('comments')}}">
                                <i class="bx bxs-message"></i>
                                <span>Comments</span>
                            </a>
                        </li>
                        @if(Session::get('user')->role->name == 'Administrador')
                            <li >
                                <a class="nav-link {{Request::is('company') ? 'show' : 'collapsed'}}" href="{{url('company')}}">
                                    <i class="bx bx-home-alt"></i>
                                    <span>Companies</span>
                                </a>
                            </li>
                        @endif
                        <li >
                            <a class="nav-link {{Request::is('anti-scan') ? 'active' : 'collapsed'}}" href="{{url('anti-scan')}}">
                                <i class="bx bxs-notification-off"></i>
                                <span>Anti-Scan</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            <li class="nav-heading">----------------</li>
            <li>
                <a class="nav-link {{Request::is('user/changePassword') ? 'active' : 'collapsed'}}" href="{{url('user/changePassword')}}">
                    <i class="bx bxs-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
        @else
            {{-- <li class="nav-heading">* PROCESSES</li> --}}
            <li >
                <a class="nav-link {{Request::is('package-check') ? 'show' : 'collapsed'}}" href="{{url('/package-check')}}">
                    <i class="bx bx-barcode-reader"></i>
                    <span>Check Stop</span>
                </a>
            </li>
        @endif
    </ul>
</aside><!-- End Sidebar-->
