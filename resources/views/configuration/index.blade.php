@extends('layout.admin')
@section('title', 'Configurations')
@section('content')
<div class="pagetitle">
  	<h1><b>CONFIGURATIONS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Configuration</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<section class="section dashboard">
    <div class="row">
        <div class="col-12 text-center mb-3">
            <hr> <h3 class="text-primary">USERS GENERAL</h3><hr>
        </div>
        @if(hasPermission('admin.index'))
        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('user')}}" style="text-decoration: none">
                <div class="card info-card ">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #943126; background: #F1948A ">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                            <h6>Users</h6>
                        </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a  href="{{url('user')}}" >Go to module <i class="bi bi-arrow-right-circle"></i></a>
                    </div>
                </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('team.index'))
        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('team')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #21618C ; background: #AED6F1 ">
                        <i class="ri-steam-line"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Teams</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a  href="{{url('team')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('driver.index'))
        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('driver')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #5B2C6F ; background: #D2B4DE ">
                        <i class="ri-map-pin-user-line"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Drivers</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('driver')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('role.index'))
        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('roles')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #239B56 ; background: #ABEBC6  ">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Roles</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('roles')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif

    </div>
    <div class="row">
        <div class="col-12 text-center mb-3">
            <hr> <h3 class="text-success">CONFIGURATION GENERAL</h3><hr>
        </div>
        <!-- Sales Card -->
        @if(hasPermission('route.index'))
        <div class="col-xxl-3 col-md-6">
            <a href="{{url('routes')}}" style="text-decoration: none">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-command"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Routes</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('routes')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Sales Card -->
        @endif
        <!-- Revenue Card -->
        @if(hasPermission('comment.index'))
        <div class="col-xxl-3 col-md-6">
            <a  href="{{url('comments')}}" style="text-decoration: none">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bxs-message"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Comments</h6>
                    </div>
                    </div>

                </div>
                <div class="card-footer text-end">
                    <a  href="{{url('comments')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Revenue Card -->
        @endif
        <!-- Revenue Card -->
        @if(hasPermission('company.index'))
        <!-- Customers Card -->
        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('company')}}" style="text-decoration: none">
                <div class="card info-card customers-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bx bx-home-alt"></i>
                        </div>
                        <div class="ps-3">
                            <h6>Companies</h6>
                        </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{url('company')}}" href="/package-inbound">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                    </div>
                </div>
            </a>

        </div><!-- End Customers Card -->
        @endif
        <!-- Revenue Card -->
        @if(hasPermission('antiscan.index'))

        <div class="col-lg-3 col-md-3 col-sm-4 col-6">
            <a href="{{url('anti-scan')}}" style="text-decoration: none">
                <div class="card info-card ">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #E74C3C; background: #F5B7B1">
                            <i class="bx bxs-notification-off"></i>
                        </div>
                        <div class="ps-3">
                            <h6>Anti-Scan</h6>
                        </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a class="" href="{{url('anti-scan')}}">Go to module <i class="bi bi-arrow-right-circle"></i></a>
                    </div>
                </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
    </div>





  </section>

@endsection
