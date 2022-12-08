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

        @if(hasPermission('orders.index'))
            <li >
                <a class="nav-link {{Request::is('orders') ? 'show' : 'collapsed'}}" href="{{url('orders')}}">
                    <i class="bx bx-book-open"></i>
                    <span>ORDERS</span>
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

            {{-- @if(hasPermission('failed.index'))
                <li >
                    <a class="nav-link {{Request::is('package-failed') ? 'show' : 'collapsed'}}" href="{{url('/package-failed')}}">
                        <i class="bx bx-x"></i>
                        <span>FAILED TASKS</span>
                    </a>
                </li>
            @endif --}}
            
            <li >
                <a class="nav-link {{Request::is('package-check') ? 'show' : 'collapsed'}}" href="{{url('/package-check')}}">
                    <i class="bx bx-barcode-reader"></i>
                    <span>CHECK STOP</span>
                </a>
            </li>
            <li>
                <a class="nav-link {{Request::is('package-age') ? 'show' : 'collapsed'}}" href="{{url('/package-age')}}">
                    <i class="bx bx-calendar-exclamation"></i>
                    <span>AGE OF PACKAGES</span>
                </a>
            </li>

            @if(hasPermission('highPriority.index'))
                <li>
                    <a class="nav-link {{Request::is('package-high-priority') ? 'show' : 'collapsed'}}" href="{{url('/package-high-priority')}}">
                        <i class="bx bx-car"></i>
                        <span>HIGH PRIORITY</span>
                    </a>
                </li>
            @endif

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
            <li style="display: none;">
                <a class="nav-link {{Request::is('assignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/assignedTeam')}}">
                    <i class="bx bx-user"></i>
                    <span>ASSIGNED</span>
                </a>
            </li>
            @endif
            @if(hasPermission('unssigned.index'))
            <li style="display: none;">
                <a class="nav-link {{Request::is('unassignedTeam') ? 'show' : 'collapsed'}}" href="{{url('/unassignedTeam')}}">
                    <i class="bx bx-user"></i>
                    <span>UNSSIGNED</span>
                </a>
            </li>
            @endif

                {{-- <li class="nav-heading" id="titleReports" >Reports</li> --}}
                @if(hasPermission('report.index'))
                <li >
                   <a class="nav-link {{Request::is('reports/general') ? 'show' : 'collapsed'}}" href="{{url('/reports/general')}}">
                       <i class="bx bxs-report"></i>
                       <span>GENERAL REPORTS</span>
                   </a>
               </li>
               @endif

                 @if(hasPermission('configuration.index'))
                 <li >
                    <a class="nav-link {{Request::is('configurations') ? 'show' : 'collapsed'}}" href="{{url('/configurations')}}">
                        <i class="ri-settings-4-line"></i>
                        <span>CONFIGURATION</span>
                    </a>
                </li>
                @endif



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
