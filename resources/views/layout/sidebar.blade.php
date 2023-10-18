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

            @if(hasPermission('nmi.index'))
                <li >
                    <a class="nav-link {{Request::is('package-nmi') ? 'show' : 'collapsed'}}" href="{{url('/package-nmi')}}">
                        <i class="bx bxs-book-reader"></i>
                        <span>NEED MORE INFORMATION</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('predispatch.index'))
                <li >
                    <a class="nav-link {{Request::is('package-pre-dispatch') ? 'show' : 'collapsed'}}" href="{{url('/package-pre-dispatch')}}">
                        <i class="bx bx-car"></i>
                        <span>PRE - DISPATCH</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('predispatch.index'))
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

            <li style="display: none;">
                <a class="nav-link {{Request::is('package-delivery') ? 'show' : 'collapsed'}}" href="{{url('package-delivery')}}">
                    <i class="bx bx-car"></i>
                    <span>DELIVERIES</span>
                </a>
            </li>
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

            @if(hasPermission('mms.index'))
                <li >
                    <a class="nav-link {{Request::is('package-mms') ? 'show' : 'collapsed'}}" href="{{url('/package-mms')}}">
                        <i class="bx bx-car"></i>
                        <span>MIDDLE MILE SCAN</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('packageLmCarrier.index'))
                <li >
                    <a class="nav-link {{Request::is('package-lm-carrier') ? 'show' : 'collapsed'}}" href="{{url('/package-lm-carrier')}}">
                        <i class="bx bx-car"></i>
                        <span>L M CARRIER</span>
                    </a>
                </li>
            @endif
            
            @if(hasPermission('packageDispatchToMiddleMile.index'))
                <li >
                    <a class="nav-link {{Request::is('package-dispatch-to-middlemile') ? 'show' : 'collapsed'}}" href="{{url('/package-dispatch-to-middlemile')}}">
                        <i class="bx bx-car"></i>
                        <span>DISPATCH TO MIDDLEMILE</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('lost.index'))
                <li >
                    <a class="nav-link {{Request::is('package-lost') ? 'show' : 'collapsed'}}" href="{{url('/package-lost')}}">
                        <i class="bx bx-car"></i>
                        <span>LOST PACKAGE</span>
                    </a>
                </li>
            @endif

            @if(hasPermission('prerts.index'))
                <li>
                    <a class="nav-link {{Request::is('package-pre-rts') ? 'show' : 'collapsed'}}" href="{{url('/package-pre-rts')}}">
                        <i class="bx bx-car"></i>
                        <span>PRE - RTS</span>
                    </a>
                </li>
            @endif

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
                <a class="nav-link {{ (Request::is('charge-company')) ? '' : 'collapsed'}}" data-bs-target="#ulFinanzas" data-bs-toggle="collapse" href="#" aria-expanded=" {{Request::is('payment-team') || Request::is('package-delivery/check' || Request::is('payment-revert')) || Request::is('to-deduct-lost-packages') || Request::is('report-invoices') ? 'true' : 'false'}}">
                  <i class="bx bxs-check-circle"></i><span>FINANCE</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="ulFinanzas" class="nav-content collapse {{ (Request::is('charge-company') || Request::is('payment-team') || Request::is('payment-revert')) || Request::is('to-deduct-lost-packages') || Request::is('report-invoices') ? 'show' : '' }}" data-bs-parent="#ulFinanzas" style="">
                    @if(hasPermission('chargeCompany.index'))
                        <li>
                            <a class="nav-link {{Request::is('charge-company') ? 'active' : 'collapsed'}}" href="{{url('charge-company')}}">
                                <i class="bx bxs-dollar-circle"></i>
                                <span>INVOICES COMPANIES</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('paymentTeam.index'))
                        <li>
                            <a class="nav-link {{Request::is('payment-team') ? 'active' : 'collapsed'}}" href="{{url('payment-team')}}">
                                <i class="bx bxs-dollar-circle"></i>
                                <span>PAYMENTS TEAMS</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('paymentTeamReverts.index'))
                        <li>
                            <a class="nav-link {{Request::is('payment-revert') ? 'active' : 'collapsed'}}" href="{{url('payment-revert')}}">
                                <i class="bx bxs-dollar-circle"></i>
                                <span>PAYMENT REVERTS</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('toDeductLostPackages.index'))
                        <li>
                            <a class="nav-link {{Request::is('to-deduct-lost-packages') ? 'active' : 'collapsed'}}" href="{{url('to-deduct-lost-packages')}}">
                                <i class="bx bxs-dollar-circle"></i>
                                <span>DEDUCT LOST PACKAGES</span>
                            </a>
                        </li>
                    @endif
                    @if(hasPermission('reportInvoices.index'))
                        <li>
                            <a class="nav-link {{Request::is('report-invoices') ? 'active' : 'collapsed'}}" href="{{url('report-invoices')}}">
                                <i class="bx bxs-dollar-circle"></i>
                                <span>REPORT INVOICES</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
    </ul>
</aside><!-- End Sidebar-->
