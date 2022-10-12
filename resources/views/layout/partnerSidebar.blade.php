<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">


        <li class="nav-heading"></li>

                {{-- <li class="nav-heading"></li>
                <li >
                    <a class="nav-link {{Request::is('partners/dashboard') ? 'active' : 'collapsed'}}" href="{{url('partners/dashboard')}}">
                        <i class="bx bxs-dashboard"></i>
                        <span>DASHBOARD</span>
                    </a>
                </li> --}}

        <li class="nav-heading" id="titleReports" >Reports</li>

        <li>
            <a class="nav-link {{Request::is('partners/report/manifest') ? 'show' : 'collapsed'}}" href="{{url('partners/report/manifest')}}">
                <i class="bx bxs-report"></i>
                <span>Manifest</span>
            </a>
        </li>

        <li>
            <a class="nav-link {{Request::is('partners/report/inbound') ? 'show' : 'collapsed'}}" href="{{url('partners/report/inbound')}}">
                <i class="bx bxs-report"></i>
                <span>Inbound</span>
            </a>
        </li>

        <li>
            <a class="nav-link {{Request::is('partners/report/dispatch') ? 'show' : 'collapsed'}}" href="{{url('partners/report/dispatch')}}">
                <i class="bx bxs-report"></i>
                <span>Dispatch</span>
            </a>
        </li>


        </li>
        <li>
            <a class="nav-link {{Request::is('partners/report/delivery') ? 'show' : 'collapsed'}}" href="{{url('partners/report/delivery')}}">
                <i class="bx bxs-report"></i>
                <span>Delivery</span>
            </a>
        </li>
        <li style="display: none;">
            <a class="nav-link {{Request::is('partners/report/notExists') ? 'show' : 'collapsed'}}" href="{{url('partners/report/notExists')}}">
                <i class="bx bxs-report"></i>
                <span>Not Exists</span>
            </a>
        </li>

        <li style="display: none;">
            <a class="nav-link {{Request::is('partners/report/return-company') ? 'show' : 'collapsed'}}" href="{{url('partners/report/return-company')}}">
                <i class="bx bxs-report"></i>
                <span>Return Company</span>
            </a>
        </li>

    </ul>
</aside><!-- End Sidebar-->
