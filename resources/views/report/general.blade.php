@extends('layout.admin')
@section('title', 'Configurations')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS GENERAL</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<section class="section dashboard">
    <div class="row">


        @if(hasPermission('reportManifest.index'))
        <div class="col-xxl-3 col-xl-12">
            <a href="{{url('/report/manifest')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #21618C ; background: #AED6F1">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Manifest</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a  href="{{url('/report/manifest')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('reportInbound.index'))
        <div class="col-xxl-3 col-xl-12">
            <a  href="{{url('/report/inbound')}}"  style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color:#004D40; background:#80CBC4">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Inbound</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a   href="{{url('/report/inbound')}}" >Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('reportDispatch.index'))
        <div class="col-xxl-3 col-xl-12">
            <a href="{{url('/report/dispatch')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #5B2C6F ; background: #D2B4DE ">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Dispatch</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('/report/dispatch')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif
        @if(hasPermission('reportDelivery.index'))
        <div class="col-xxl-3 col-xl-12">
            <a href="{{url('/report/delivery')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #039BE5 ; background: #81D4FA  ">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Delivery</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('/report/delivery')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Customers Card -->
        @endif

    </div>
    <div class="row">

        <!-- Sales Card -->
        @if(hasPermission('reportFailed.index'))
        <div class="col-xxl-3 col-md-6">
            <a href="{{url('/report/failed')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #943126; background: #F1948A ">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Failed</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('/report/failed')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Sales Card -->
        @endif
        <!-- Revenue Card -->
        @if(hasPermission('reportNotexists.index'))
        <div class="col-xxl-3 col-md-6">
            <a href="{{url('/report/notExists')}}" style="text-decoration: none">
            <div class="card info-card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #C2185B ; background:#F48FB1  ">
                        <i class="bx bxs-report"></i>
                    </div>
                    <div class="ps-3">
                        <h6>Not exists</h6>
                    </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{url('/report/notExists')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
            </a>
        </div><!-- End Sales Card -->
        @endif
        <!-- Revenue Card -->
        @if(hasPermission('reportReturncompany.index'))
            <!-- Customers Card -->
            <div class="col-xxl-3 col-xl-12">
                <a href="{{url('/report/return-company')}}" style="text-decoration: none">
                    <div class="card info-card customers-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bx bxs-report"></i>
                            </div>
                            <div class="ps-3">
                                <h6>Return company</h6>
                            </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{url('/report/return-company')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                        </div>
                    </div>
                </a>
            </div><!-- End Customers Card -->
        @endif

        @if(hasPermission('reportMassQuery.index'))
            <!-- Customers Card -->
            <div class="col-xxl-3 col-xl-12">
                <a href="{{url('/report/mass-query')}}" style="text-decoration: none">
                    <div class="card info-card customers-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #21618C ; background: #AED6F1">
                                <i class="bx bxs-report"></i>
                            </div>
                            <div class="ps-3">
                                <h6>Mass Query</h6>
                            </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{url('/report/mass-query')}}">Go to report <i class="bi bi-arrow-right-circle"></i></a>
                        </div>
                    </div>
                </a>
            </div><!-- End Customers Card -->
        @endif

    </div>





  </section>

@endsection
