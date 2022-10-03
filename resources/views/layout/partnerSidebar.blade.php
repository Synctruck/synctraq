<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li >
            <div id="google_translate_element" class="google"></div>
        </li>

        <li class="nav-heading"></li>

                <li class="nav-heading"></li>
                <li >
                    <a class="nav-link {{Request::is('partners/dashboard') ? 'active' : 'collapsed'}}" href="{{url('partners/dashboard')}}">
                        <i class="bx bxs-dashboard"></i>
                        <span>DASHBOARD</span>
                    </a>
                </li>

                <li class="nav-heading" id="titleReports" >Reports</li>
                <li class="nav-item" id="liUlReports">
                    <a class="nav-link
                            {{
                                Request::is('partners/report/failed') ||
                                Request::is('partners/report/assigns') ||
                                Request::is('partners/report/dispatch') ||
                                Request::is('partners/report/delivery') ||
                                Request::is('partners/report/inbound') ||
                                Request::is('partners/report/manifest') ||
                                Request::is('partners/report/notExists') ||
                                Request::is('partners/report/return-company')
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
                                Request::is('partners/report/failed') ||
                                Request::is('partners/report/assigns') ||
                                Request::is('partners/report/dispatch') ||
                                Request::is('partners/report/delivery') ||
                                Request::is('partners/report/inbound') ||
                                Request::is('partners/report/manifest') ||
                                Request::is('partners/report/notExists') ||
                                Request::is('partners/report/return-company')
                                ?
                                    'collapse show'
                                :
                                    'collapse'
                            }}" data-bs-parent="#ulReports"
                    >

                        <li>
                            <a class="nav-link {{Request::is('partners/report/manifest') ? 'show' : 'collapsed'}}" href="{{url('/report/manifest')}}">
                                <i class="bx bxs-report"></i>
                                <span>Manifest</span>
                            </a>
                        </li>

                        <li>
                            <a class="nav-link {{Request::is('partners/report/inbound') ? 'show' : 'collapsed'}}" href="{{url('/report/inbound')}}">
                                <i class="bx bxs-report"></i>
                                <span>Inbound</span>
                            </a>
                        </li>

                        <li>
                            <a class="nav-link {{Request::is('partners/report/dispatch') ? 'show' : 'collapsed'}}" href="{{url('/report/dispatch')}}">
                                <i class="bx bxs-report"></i>
                                <span>Dispatch</span>
                            </a>
                        </li>


                        </li>
                        <li>
                            <a class="nav-link {{Request::is('partners/report/delivery') ? 'show' : 'collapsed'}}" href="{{url('/report/delivery')}}">
                                <i class="bx bxs-report"></i>
                                <span>Delivery</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{Request::is('partners/report/notExists') ? 'show' : 'collapsed'}}" href="{{url('/report/notExists')}}">
                                <i class="bx bxs-report"></i>
                                <span>Not Exists</span>
                            </a>
                        </li>

                        <li>
                            <a class="nav-link {{Request::is('partners/report/return-company') ? 'show' : 'collapsed'}}" href="{{url('/report/return-company')}}">
                                <i class="bx bxs-report"></i>
                                <span>Return Company</span>
                            </a>
                        </li>
                    </ul>
                </li>
    </ul>
</aside><!-- End Sidebar-->
