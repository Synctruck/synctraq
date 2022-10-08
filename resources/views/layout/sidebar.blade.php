<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li >
            <div id="google_translate_element" class="google"></div>
        </li>
        @if(hasPermission('packageBlocked.index'))
        <li class="nav-heading"></li>
        <li >
            <a class="nav-link {{Request::is('package-blocked') ? 'active' : 'collapsed'}}" href="{{url('package-blocked')}}">
                <i class="bx bx-block"></i>
                <span>PACKAGE BLOCKED</span>
            </a>
        </li>
        @endif
            @if(hasPermission('dashboard.index'))
                <li class="nav-heading"></li>
                <li >
                    <a class="nav-link {{Request::is('dashboard') ? 'active' : 'collapsed'}}" href="{{url('dashboard')}}">
                        <i class="bx bxs-dashboard"></i>
                        <span>DASHBOARD</span>
                    </a>
                </li>
            @endif
            @if(hasPermission('manifest.index'))
                <li >
                    <a class="nav-link {{Request::is('package-manifest') ? 'active' : 'collapsed'}}" href="{{url('package-manifest')}}">
                        <i class="bx bxs-box"></i>
                        <span>MANIFEST</span>
                    </a>
                </li>
            @endif

                {{-- <li class="nav-heading">* PROCESSES</li> --}}
            @if(hasPermission('inbound.index'))
                <li >
                    <a class="nav-link {{Request::is('package-inbound') ? 'show' : 'collapsed'}}" href="{{url('/package-inbound')}}">
                        <i class="bx bx-barcode-reader"></i>
                        <span>INBOUND</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('dispatch.index'))
                <li >
                    <a class="nav-link {{Request::is('package-dispatch') ? 'show' : 'collapsed'}}" href="{{url('/package-dispatch')}}">
                        <i class="bx bx-car"></i>
                        <span>DISPATCH</span>
                    </a>
                </li>
            @endif


                <li >
                    <a class="nav-link {{Request::is('package-check') ? 'show' : 'collapsed'}}" href="{{url('/package-check')}}">
                        <i class="bx bx-barcode-reader"></i>
                        <span>CHECK STOP</span>
                    </a>
                </li>


                {{-- @if(Auth::user()->role->name == 'Administrador')
                    <li >
                        <a class="nav-link {{Request::is('assigned') ? 'show' : 'collapsed'}}" href="{{url('/assigned')}}">
                            <i class="bx bx-user"></i>
                            <span>Assigned Team</span>
                        </a>
                    </li>
                @endif --}}

                <li style="display: none;">
                    <a class="nav-link {{Request::is('package-delivery') ? 'show' : 'collapsed'}}" href="{{url('package-delivery')}}">
                        <i class="bx bx-car"></i>
                        <span>DELIVERIES</span>
                    </a>
                </li>




                {{-- <li class="nav-heading">* DESELECT</li> --}}
                {{-- @if(Auth::user()->role->name == 'Administrador')
                    <li >
                        <a class="nav-link {{Request::is('package-not-exists') ? 'show' : 'collapsed'}}" href="{{url('/package-not-exists')}}">
                            <i class="bx bx-barcode-reader"></i>
                            <span>Not Exists</span>
                        </a>
                    </li>
                @endif --}}

            @if(hasPermission('reinbound.index'))
                <li >
                    <a class="nav-link {{Request::is('package/return') ? 'show' : 'collapsed'}}" href="{{url('/package/return')}}">
                        <i class="bx bx-car"></i>
                        <span>RE-INBOUND</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('warehouse.index'))
                <li >
                    <a class="nav-link {{Request::is('package-warehouse') ? 'show' : 'collapsed'}}" href="{{url('/package-warehouse')}}">
                        <i class="bx bx-car"></i>
                        <span>WAREHOUSE</span>
                    </a>
                </li>
            @endif

                {{-- @if(Auth::user()->role->name == 'Administrador')
                    <li >
                        <a class="nav-link {{Request::is('unassigned') ? 'show' : 'collapsed'}}" href="{{url('/unassigned')}}">
                            <i class="bx bx-user"></i>
                            <span>Unssigned Team</span>
                        </a>
                    </li>
                @endif --}}


            @if(hasPermission('assigned.index'))
            <li >
                <a class="nav-link {{Request::is('assignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/assignedTeam')}}">
                    <i class="bx bx-user"></i>
                    <span>ASSIGNED</span>
                </a>
            </li>
            @endif
            @if(hasPermission('unssigned.index'))
            <li >
                <a class="nav-link {{Request::is('unassignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/unassignedTeam')}}">
                    <i class="bx bx-user"></i>
                    <span>UNSSIGNED</span>
                </a>
            </li>
            @endif

                <li class="nav-heading" id="titleReports" >Reports</li>
                <li class="nav-item" id="liUlReports">
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
                            }}" data-bs-target="#ulReports" data-bs-toggle="collapse" href="#"
                    >
                        <i class="bi bi-journal-text"></i><span>GENERAL REPORTS</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="ulReports" class="nav-content
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
                            }}" data-bs-parent="#ulReports"
                    >
                    @if(hasPermission('reportManifest.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/manifest') ? 'show' : 'collapsed'}}" href="{{url('/report/manifest')}}">
                                <i class="bx bxs-report"></i>
                                <span>Manifest</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('reportInbound.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/inbound') ? 'show' : 'collapsed'}}" href="{{url('/report/inbound')}}">
                                <i class="bx bxs-report"></i>
                                <span>Inbound</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('reportDispatch.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/dispatch') ? 'show' : 'collapsed'}}" href="{{url('/report/dispatch')}}">
                                <i class="bx bxs-report"></i>
                                <span>Dispatch</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('reportDelivery.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/delivery') ? 'show' : 'collapsed'}}" href="{{url('/report/delivery')}}">
                                <i class="bx bxs-report"></i>
                                <span>Delivery</span>
                            </a>
                        </li>
                    @endif

                    @if(hasPermission('reportFailed.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/failed') ? 'show' : 'collapsed'}}" href="{{url('/report/failed')}}">
                                <i class="bx bxs-report"></i>
                                <span>Failed</span>
                            </a>
                        </li>
                    @endif

                    @if(hasPermission('reportNotexists.index'))
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
                    @endif
                    @if(hasPermission('reportReturncompany.index'))
                        <li>
                            <a class="nav-link {{Request::is('report/return-company') ? 'show' : 'collapsed'}}" href="{{url('/report/return-company')}}">
                                <i class="bx bxs-report"></i>
                                <span>Return Company</span>
                            </a>
                        </li>
                    @endif

                    </ul>
                </li>


                <li class="nav-heading" id="titleMaintenances">MAINTENANCES</li>
                <li class="nav-item" id="liUlUsers">
                    <a class="nav-link {{ (Request::is('user') || Request::is('team') || Request::is('driver') || Request::is('validator')   || Request::is('viewer') || Request::is('roles') ) ? '' : 'collapsed'}}" data-bs-target="#ulUsers" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('team') ? 'true' : 'false'}}">
                      <i class="bi bi-person"></i><span>USERS GENERAL</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="ulUsers" class="nav-content collapse {{Request::is('user') || Request::is('team') || Request::is('roles') || Request::is('driver') || Request::is('validator')   || Request::is('viewer')? 'show' : ''}}" data-bs-parent="#ulUsers" style="">

                        @if(hasPermission('admin.index'))
                        <li >
                            <a class="nav-link {{Request::is('user') ? 'show' : 'collapsed'}}" href="{{url('user')}}">
                                <i class="bi bi-person"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        @endif

                        @if(hasPermission('team.index'))
                        <li >
                            <a class="nav-link {{Request::is('team') ? 'active' : 'collapsed'}}" href="{{url('team')}}">
                                <i class="bi bi-person"></i>
                                <span>Teams</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('driver.index'))
                        <li >
                            <a class="nav-link {{Request::is('driver') ? 'active' : 'collapsed'}}" href="{{url('driver')}}">
                                <i class="bi bi-person"></i>
                                <span>Drivers</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('viewer.index'))
                        <li >
                            <a class="nav-link {{Request::is('viewer') ? 'active' : 'collapsed'}}" href="{{url('viewer')}}">
                                <i class="bi bi-person"></i>
                                <span>Viewers</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('validator.index'))
                        <li >
                            <a class="nav-link {{Request::is('validator') ? 'active' : 'collapsed'}}" href="{{url('validator')}}">
                                <i class="bi bi-person"></i>
                                <span>Validators</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('role.index'))
                        <li >
                            <a class="nav-link {{Request::is('roles') ? 'active' : 'collapsed'}}" href="{{url('roles')}}">
                                <i class="bi bi-person"></i>
                                <span>Roles</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <li class="nav-item" id="liUlConfiguration">
                    <a class="nav-link {{ (Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan')) ? '' : 'collapsed'}}" data-bs-target="#ulConfiguration" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan') ? 'true' : 'false'}}">
                      <i class="bi bi-person"></i><span>CONFIGURATION GENERAL</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="ulConfiguration" class="nav-content collapse {{(Request::is('routes') || Request::is('comments') || Request::is('company') || Request::is('anti-scan'))? 'show' : ''}}" data-bs-parent="#ulConfiguration" style="">

                        @if(hasPermission('route.index'))
                        <li >
                            <a class="nav-link {{Request::is('routes') ? 'show' : 'collapsed'}}" href="{{url('routes')}}">
                                <i class="bx bx-command"></i>
                                <span>Routes</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('comment.index'))
                        <li >
                            <a class="nav-link {{Request::is('comments') ? 'active' : 'collapsed'}}" href="{{url('comments')}}">
                                <i class="bx bxs-message"></i>
                                <span>Comments</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('company.index'))
                            <li >
                                <a class="nav-link {{Request::is('company') ? 'show' : 'collapsed'}}" href="{{url('company')}}">
                                    <i class="bx bx-home-alt"></i>
                                    <span>Companies</span>
                                </a>
                            </li>
                        @endif

                        @if(hasPermission('antiscan.index'))
                        <li>
                            <a class="nav-link {{Request::is('anti-scan') ? 'active' : 'collapsed'}}" href="{{url('anti-scan')}}">
                                <i class="bx bxs-notification-off"></i>
                                <span>Anti-Scan</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <li class="nav-item" id="liUlFinanzas">
                    <a class="nav-link {{ (Request::is('package-delivery/finance') || Request::is('package-delivery/check')) ? '' : 'collapsed'}}" data-bs-target="#ulFinanzas" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('package-delivery/finance') || Request::is('package-delivery/check') ? 'true' : 'false'}}">
                      <i class="bx bxs-check-circle"></i><span>FINANCE</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="ulFinanzas" class="nav-content collapse {{(Request::is('package-delivery/check') || Request::is('package-delivery/finance') || Request::is('company') || Request::is('anti-scan'))? 'show' : ''}}" data-bs-parent="#ulFinanzas" style="">
                        @if(hasPermission('checkDelivery.index'))
                        <li >
                            <a class="nav-link {{Request::is('package-delivery/check') ? 'show' : 'collapsed'}}" href="{{url('/package-delivery/check')}}">
                                <i class="bx bxs-check-circle"></i>
                                <span>CHECK - UNCHECK DELIVERY</span>
                            </a>
                        </li>
                        @endif
                        @if(hasPermission('validatedDelivery.index'))
                            <li>
                                <a class="nav-link {{Request::is('package-delivery/finance') ? 'active' : 'collapsed'}}" href="{{url('package-delivery/finance')}}">
                                    <i class="bx bxs-dollar-circle"></i>
                                    <span>VALIDATE DELIVERY</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

            {{-- <li class="nav-heading">----------------</li>
            <li>
                <a class="nav-link {{Request::is('user/changePassword') ? 'active' : 'collapsed'}}" href="{{url('user/changePassword')}}">
                    <i class="bx bxs-key"></i>
                    <span>Change Password</span>
                </a>
            </li> --}}

    </ul>
</aside><!-- End Sidebar-->
