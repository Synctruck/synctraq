<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>@yield('title')</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token" content="{{csrf_token()}}">

    <!-- Favicons -->
    <link href="{{asset('img/favicon.ico')}}" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <!-- Google Fonts -->


    <!-- Vendor CSS Files -->
    <link href="{{asset('admin/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{asset('admin/assets/css/style.css')}}?{{time()}}" rel="stylesheet">
    <link href="{{asset('admin/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="{{asset('js/barcode.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
  <!-- =======================================================
  * Template Name: NiceAdmin - v2.2.0
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>
<style>
    body
    {
        font-family: 'Inconsolata', monospace;
    }
    #loader {
        position: fixed;
        left: 0px;
        top: 0px;
        width: 100%;
        height: 100%;
        z-index: 9999;
        background: url('{{asset("cargando.gif")}}') 50% 50% no-repeat rgb(0,0,0);
        opacity: 0.92;
        display: none;
    }

    .verticalTextLeft
    {
        writing-mode: vertical-lr;
        transform: rotate(180deg);
    }

    .verticalTextRight
    {
        writing-mode: vertical-lr;
        transform: rotate(360deg);
    }
    @media (min-width: 992px) {
      .modal-lg,
    .modal-xl {
        max-width: 1250px;
      }
    }

    .text-right{
       text-align: right;
    }

    #container {
    height: 400px;
}

    .highcharts-figure,
    .highcharts-data-table table {
        min-width: 310px;
        max-width: 800px;
        margin: 1em auto;
    }

    .highcharts-data-table table {
        font-family: Verdana, sans-serif;
        border-collapse: collapse;
        border: 1px solid #ebebeb;
        margin: 10px auto;
        text-align: center;
        width: 100%;
        max-width: 500px;
    }

    .highcharts-data-table caption {
        padding: 1em 0;
        font-size: 1.2em;
        color: #555;
    }

    .highcharts-data-table th {
        font-weight: 600;
        padding: 0.5em;
    }

    .highcharts-data-table td,
    .highcharts-data-table th,
    .highcharts-data-table caption {
        padding: 0.5em;
    }

    .highcharts-data-table thead tr,
    .highcharts-data-table tr:nth-child(even) {
        background: #f8f8f8;
    }

    .highcharts-data-table tr:hover {
        background: #f1f7ff;
    }
</style>
<body id="bodyAdmin">

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="{{url('home')}}" class="logo d-flex align-items-center">
        <img src="{{asset('img/logo.png')}}" width="128" height="175" alt="">
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    <div class="search-bar">
        @if(Auth::check())
            <div class="row">
                <div class="col-lg-4">
                    <form id="formSearhPackage" class="search-form d-flex align-items-center" onsubmit="SearchPackage(event)">
                        <input type="text" id="searchPackage" name="searchPackage" placeholder="Search PACKAGE ID" title="Enter search keyword">
                        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <form class="search-form d-flex align-items-center" onsubmit="SearchPackageTask(event)">
                        <input type="text" id="searchPackageTask" name="searchPackageTask" placeholder="Search TASK#" title="Enter search keyword">
                        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <button class="btn btn-success form-control" onclick="ViewModalSearchFilters();">Search by filters</button>
                </div>
                <div class="alert alert-danger text-center w-100" style="margin-bottom: 0; font-size: 1rem;">
                Remember, Synctruck will no longer be available. Use <a href="https://platform.syncfreight.com" target="_blank" style="color: white; text-decoration: underline;">https://platform.syncfreight.com</a> in the future.
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-6">
                    <a href="{{url('/')}}" class="btn btn-success form-control">Go to Login</a>
                </div>
            </div>
        @endif
    </div><!-- End Search Bar -->
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon-->

        <li class="nav-item dropdown" style="display: none;">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <span class="badge bg-primary badge-number" style="font-size: 16px;">{{date('d/m/Y')}}</span>
          </a><!-- End Notification Icon -->

        </li><!-- End Notification Nav -->

        <li class="nav-item dropdown" style="display: none;">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number">4</span>
          </a><!-- End Notification Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              You have 4 new notifications
              <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-exclamation-circle text-warning"></i>
              <div>
                <h4>Lorem Ipsum</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>30 min. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-x-circle text-danger"></i>
              <div>
                <h4>Atque rerum nesciunt</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>1 hr. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <h4>Sit rerum fuga</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>2 hrs. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-info-circle text-primary"></i>
              <div>
                <h4>Dicta reprehenderit</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>4 hrs. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>
            <li class="dropdown-footer">
              <a href="#">Show all notifications</a>
            </li>

          </ul><!-- End Notification Dropdown Items -->

        </li><!-- End Notification Nav -->

        <li class="nav-item dropdown" style="display: none;">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-chat-left-text"></i>
            <span class="badge bg-success badge-number">3</span>
          </a><!-- End Messages Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
            <li class="dropdown-header">
              You have 3 new messages
              <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                {{-- <img src="assets/img/messages-1.jpg" alt="" class="rounded-circle"> --}}
                <div>
                  <h4>Maria Hudson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>4 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                {{-- <img src="assets/img/messages-2.jpg" alt="" class="rounded-circle"> --}}
                <div>
                  <h4>Anna Nelson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>6 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                {{-- <img src="assets/img/messages-3.jpg" alt="" class="rounded-circle"> --}}
                <div>
                  <h4>David Muldon</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>8 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="dropdown-footer">
              <a href="#">Show all messages</a>
            </li>

          </ul><!-- End Messages Dropdown Items -->

        </li><!-- End Messages Nav -->

        @if(Auth::check())
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <img src="{{Auth::user()->url_image}}" alt="Profile" class="rounded-circle">
                    <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name .' '. Auth::user()->nameOfOwner }}</span>
                </a><!-- End Profile Iamge Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        @if(Auth::user()->role->name != 'Team')
                            <h6>{{Auth::user()->name .' '. Auth::user()->nameOfOwner}}</h6>
                        @else
                            <h6>{{Auth::user()->name}}</h6>
                        @endif

                        <span>{{Auth::user()->role->name}}</span>
                    </li>
                    <li>
                      <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{url('/profile')}}">
                            <i class="bi bi-person"></i>
                            <span>My profile</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{url('user/logout')}}">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->
        @endif
      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->

    <!-- ======= Sidebar ======= -->
    @include('layout.sidebar')

    <main id="main" class="main">
        <div id="loader"></div>

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-primary" id="titleModalHistory">Historial Package</h5>
                                <h6 class="text-primary" id="subTitleModalHistory"></h6>
                                <button id="btnReturnToDebrief" class="btn btn-warning mt-2" style="display: none;" onclick="ReturnToDebrief()">RETURN TO DEBRIEF</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <h6 class="text-primary" id="subTitleModalHistory"></h6>
                            </div>
                        </div>
                    </div>
                    <form action="" onsubmit="SaveEditPackageHistory(event);">
                        <div class="modal-body">
                            <input type="hidden" id="idPackage" name="Reference_Number_1">
                            <div class="row">
                                <div class="col-lg-3 form-group">
                                    <label for="contactName">CLIENT</label>
                                    <input type="text" id="contactName" name="contactName" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactPhone">CONTACT</label>
                                    <input type="text" id="contactPhone" name="contactPhone" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactAddress">ADDRESS</label>
                                    <input type="text" id="contactAddress" name="contactAddress" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactAddress2">ADDRESS 2</label>
                                    <input type="text" id="contactAddress2" name="contactAddress2" class="form-control">
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactCity">CITY</label>
                                    <input type="text" id="contactCity" name="contactCity" class="form-control" required>
                                </div>

                                <div class="col-lg-3 form-group">
                                    <label for="contactState">STATE</label>
                                    <input type="text" id="contactState" name="contactState" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactZipCode">ZIP C</label>
                                    <input type="text" id="contactZipCode" name="contactZipCode" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactWeight">WEIGHT</label>
                                    <input type="text" id="contactWeight" name="contactWeight" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactRoute">ROUTE</label>
                                    <input type="text" id="contactRoute" name="contactRoute" class="form-control" required>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactRoute">HIGH PRIORITY</label>
                                    <select name="" id="highPriority" class="form-control">
                                        <option value="Normal">NORMAL</option>
                                        <option value="High">HIGH</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 form-group">
                                    <label for="lengthSearch">LENGTH</label>
                                    <input type="text" id="lengthSearch" name="lengthSearch" class="form-control" readonly>
                                </div>
                                <div class="col-lg-2 form-group">
                                    <label for="heightSearch">HEIGHT</label>
                                    <input type="text" id="heightSearch" name="heightSearch" class="form-control" readonly>
                                </div>
                                <div class="col-lg-2 form-group">
                                    <label for="widthSearch">WIDTH</label>
                                    <input type="text" id="widthSearch" name="widthSearch" class="form-control" readonly>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="volumeSearch">VOLUME</label>
                                    <input type="text" id="volumeSearch" name="volumeSearch" class="form-control" readonly>
                                </div>
                                <div class="col-lg-12 form-group">
                                    <label for="contactState">INTERNAL COMMENT</label>
                                    <textarea name="internalComment" id="internalComment" cols="10" rows="2" class="form-control"></textarea>
                                </div>
                                <div class="col-lg-3">
                                    <label for="">Onfleet Task#</label>
                                    <input type="text" id="taskOnfleetHistory" class="form-control" placeholder="Task #">
                                </div>
                                <div class="col-lg-3">
                                    <label for="">Onfleet Note</label>
                                    <input type="text" id="notesOnfleetHistory" class="form-control" placeholder="Notes">
                                </div>
                                <div class="col-lg-6">
                                    <label for=""><a href="#viewMap" onclick="ViewMap();">GeoLocation (Delivery)</a></label>
                                    <input type="text" id="latitudeLongitude" class="form-control" placeholder="Notes" readonly>
                                </div>
                                <div class="col-lg-3 form-group">
                                    <br>
                                    <button class="btn btn-primary form-control">Updated</button>
                                </div>
                                <div id="divSynchronizePackage" class="col-lg-3 form-group" style="display: none;">
                                    @if(Auth::user()->role->name == 'Master')
                                        <br>
                                        <button type="button" class="btn btn-warning form-control" onclick="RegisterInland();">Synchronize Package</button>
                                    @endif
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 form-group">
                                    <label for="" class="form">Actual Status</label>
                                    <input type="text" id="actualStatus" class="form-control">
                                    <div id="divActualStatus" class="alert alert-danger">
                                        <b>This package was delete of the manifest</b>
                                    </div>
                                </div>
                                <div id="divBtnHistoryNMI" class="col-lg-3 form-group" style="display: none;">
                                    <label for="" class="text-white">--</label>
                                    <button type="button" id="btnHistoryNMI" class="btn btn-success form-control" onclick="ShowHistoryNMI();">Show History NMI</button>
                                </div>
                            </div>
                            <div id="divTableHistoryNMI" class="row" style="display: none;">
                                <div class="col-lg-12">
                                    <h3 class="text-primary text-center">PROCESS HISTORY - NMI</h3>
                                </div>
                                <div class="col-lg-12">
                                    <table id="tableHistoryPackage" class="table table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>USER</th>
                                                <th>PACKAGE ID</th>
                                                <th>INTERNAL COMMENT</th>
                                                <th>STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableHistoryNMITbody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-lg-12">
                                    <h3 class="text-success text-center">PROCESS HISTORY</h3>
                                </div>
                                <div class="col-lg-12">
                                    <table id="tableHistoryPackage" class="table table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE SYNTRUCK</th>
                                                <th>DATE</th>
                                                <th>USER</th>
                                                <th>STATUS</th>
                                                <th>DESCRIPTION</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableHistoryPackageTbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" id="btnBackGoTo" class="btn btn-primary close" style="display: none;" onclick="ViewModalSearchFilters()">Go back</button>
                        <button type="button" id="btnCloseHistorialPackage" class="btn btn-secondary close" data-dismiss="modal" aria-label="Close" onclick="CloseModal('exampleModal');">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="exampleModalTask" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-primary">Status Package Track#</h5>
                                <h6 class="text-primary"></h6>
                            </div>
                        </div>
                    </div>
                    <form action="" onsubmit="SaveEditPackageHistory(event);">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-6 form-group">
                                    <label for="teamOnfleet">TASK #</label>
                                    <input type="text" id="taskOnfleet" name="teamOnfleet" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="teamOnfleet">PACKAGE ID</label>
                                    <input type="text" id="packageID" name="teamOnfleet" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="teamOnfleet">TEAM</label>
                                    <input type="text" id="teamOnfleet" name="teamOnfleet" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="driverOnfleet">DRIVER</label>
                                    <input type="text" id="driverOnfleet" name="driverOnfleet" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="contactOnfleetName">CLIENT</label>
                                    <input type="text" id="contactOnfleetName" name="contactOnfleetName" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="contactOnfleetPhone">CONTACT</label>
                                    <input type="text" id="contactOnfleetPhone" name="contactOnfleetPhone" class="form-control" readonly>
                                </div>
                                <div class="col-lg-12 form-group">
                                    <label for="contactOnfleetAddress">ADDREESS</label>
                                    <input type="text" id="contactOnfleetAddress" name="contactOnfleetAddress" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="contactOnfleetCity">CITY</label>
                                    <input type="text" id="contactOnfleetCity" name="contactOnfleetCity" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="contactOnfleetState">STATE</label>
                                    <input type="text" id="contactOnfleetState" name="contactOnfleetState" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="contactOnfleetZipCode">ZIP C</label>
                                    <input type="text" id="contactOnfleetZipCode" name="contactOnfleetZipCode" class="form-control" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label for="statusOnfleet">Status</label>
                                    <input type="text" id="statusOnfleet" name="statusOnfleet" class="form-control" readonly>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <table id="tableOnfleet" class="table table-condensed table-bordered">
                                            <tbody id="tableOnfleetTbody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close" data-dismiss="modal" aria-label="Close" onclick="CloseModal('exampleModalTask');">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="searchByFiltersModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-primary">SEARCH PACKAGES BY FILTERS</h5>
                            </div>
                        </div>
                    </div>
                    <form action="" onsubmit="SearchPackageByFilters(event);">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-3 form-group">
                                    <label for="clientNameFilters">CLIENT</label>
                                    <input type="text" id="clientNameFilters" name="clientNameFilters" class="form-control">
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactPhoneFilters">CONTACT</label>
                                    <input type="text" id="contactPhoneFilters" name="contactPhoneFilters" class="form-control">
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactAddressFilters">ADDRESS</label>
                                    <input type="text" id="contactAddressFilters" name="contactAddressFilters" class="form-control">
                                </div>
                                <div class="col-lg-3 form-group">
                                    <label for="contactCity">-</label>
                                    <button class="btn btn-primary form-control">Search</button>
                                </div>
                            </div>
                            <hr>
                            <div class="row table-responsive">
                                <div class="col-lg-12">
                                    <table id="tableListPackageByFiltersTable" class="table table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>ACTUAL STATUS</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP C</th>
                                                <th>ROUTE</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableListPackageByFiltersBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close" data-dismiss="modal" aria-label="Close" onclick="ViewModalSearchFilters();">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!--Contenid -->
        @yield('content')
    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    {{-- <footer id="footer" class="footer">
        <div class="copyright" style="display: none;">
          &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
        </div>
        <div class="credits" style="display: none;">
          <!-- All the links in the footer should remain intact. -->
          <!-- You can delete the links only if you purchased the pro version. -->
          <!-- Licensing information: https://bootstrapmade.com/license/ -->
          <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
          Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
        </div>
    </footer><!-- End Footer --> --}}

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="{{asset('admin/assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/chart.js/chart.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/echarts/echarts.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/quill/quill.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('admin/assets/vendor/php-email-form/validate.js')}}"></script>

    <!-- Template Main JS File -->

    <script src="{{asset('admin/assets/js/main.js')}}"></script>
    <script>
        var limitToExport = 60;
        var url_general = '{{url('/')}}/';

        function ReturnToDebrief()
        {
            document.getElementById('btnCloseHistorialPackage').click();

            let myModal = new bootstrap.Modal(document.getElementById('modalPackagesListDebrief'), {

                keyboard: true
            });

            myModal.show();
        }

        function RegisterInland()
        {
            let Reference_Number_1 = document.getElementById('searchPackage').value;

            fetch("{{url('package/insert-inland')}}/"+ Reference_Number_1)
            .then(response => response.json())
            .then(response => {

                if(response.status == 201)
                {
                    document.getElementById('divSynchronizePackage').style.display = 'none';

                    swal('Correct', 'The package was registered', 'success');
                }
                else
                {
                    swal('Error', response.output.error, 'error');
                }
            });
        }

        function LoadingShow()
        {
            //document.getElementById('loader').style.display = 'block';
        }

        function LoadingHide()
        {
            //document.getElementById('loader').style.display = 'none';
        }

        function LoadingShowMap()
        {
            document.getElementById('loader').style.display = 'block';
        }

        function LoadingHideMap()
        {
            document.getElementById('loader').style.display = 'none';
        }

        function ViewMap()
        {
            let latitudeLongitude = document.getElementById('latitudeLongitude').value;

            window.open('https://maps.google.com/?q='+ latitudeLongitude);
        }

        function SearchPackage(e)
        {
            searchGlobal = 0;

            e.preventDefault();

            SearchPackageReferenceId();
        }

        function ShowHistoryNMI()
        {
            document.getElementById('divTableHistoryNMI').style.display = 'block';
            document.getElementById('btnHistoryNMI').innerHTML          = 'Hide History NMI';

            document.getElementById('btnHistoryNMI').setAttribute('onclick', 'HideHistoryNMI()');
            document.getElementById('btnHistoryNMI').setAttribute('class', 'btn btn-danger form-control');
        }

        function HideHistoryNMI()
        {
            document.getElementById('divTableHistoryNMI').style.display = 'none';
            document.getElementById('btnHistoryNMI').innerHTML          = 'Show History NMI';

            document.getElementById('btnHistoryNMI').setAttribute('onclick', 'ShowHistoryNMI()');
            document.getElementById('btnHistoryNMI').setAttribute('class', 'btn btn-success form-control');
        }

        function ListHistoryNMI(packageHistoryNMIList)
        {
            let tableHistoryNMITbody       = document.getElementById('tableHistoryNMITbody');

            tableHistoryNMITbody.innerHTML = '';

            let tr = '';

            packageHistoryNMIList.forEach( historyNMI =>  {

                tr =    '<tr>'+
                            '<td><b>'+ historyNMI.created_at.substring(5, 7) +'-'+ historyNMI.created_at.substring(8, 10) +'-'+ historyNMI.created_at.substring(0, 4) +'</b><br>'+ historyNMI.created_at.substring(11, 19) +
                            '</td>'+
                            '<td><b>'+ historyNMI.user.name +' '+ historyNMI.user.nameOfOwner +'</b><br>'+ historyNMI.user.email +'</td>'+
                            '<td><b>'+ historyNMI.Reference_Number_1 +'</b></td>'+
                            '<td><b>'+ historyNMI.internalComment +'</b></td>'+
                            '<td>'+ historyNMI.status +'</td>'+
                        '</tr>';

                tableHistoryNMITbody.insertRow(-1).innerHTML = tr;
            });
        }

        function SearchPackageReferenceId()
        {
            document.getElementById('btnReturnToDebrief').style.display    = 'none';
            document.getElementById('divTableHistoryNMI').style.display    = 'none';
            document.getElementById('divBtnHistoryNMI').style.display      = 'none';
            document.getElementById('divSynchronizePackage').style.display = 'none';

            document.getElementById('actualStatus').style.display    = 'none';
            document.getElementById('divActualStatus').style.display = 'none';

            let PACKAGE_ID = document.getElementById('searchPackage').value;

            fetch(url_general +'package-history/search/'+ PACKAGE_ID)
            .then(response => response.json())
            .then(response => {

                let packageBlocked        = response.packageBlocked;
                let packageDelete         = response.packageDelete;
                let packageHistoryList    = response.packageHistoryList;
                let packageHistoryNMIList = response.packageHistoryNeeMoreInformation;
                let packageDelivery       = response.packageDelivery;
                let packageDispatch       = response.packageDispatch;
                let notesOnfleet          = response.notesOnfleet;
                let latitudeLongitude     = response.latitudeLongitude;
                let actualStatus          = response.actualStatus;
                let existsInInland        = response.existsInInland;
                let packagesweights       = response.packagesweights;

                if(packagesweights){
                    console.log("messi",packagesweights.length2);
                }

                if(packageHistoryList.length > 0)
                {
                    if(packageHistoryList[0].company != 'INLAND LOGISTICS' && existsInInland == false)
                    {
                        document.getElementById('divSynchronizePackage').style.display = 'block';

                    }
                }

                if(packageHistoryNMIList.length > 0)
                {
                    document.getElementById('divBtnHistoryNMI').style.display   = 'block';
                }

                ListHistoryNMI(packageHistoryNMIList);

                document.getElementById('taskOnfleetHistory').value           = '';
                document.getElementById('notesOnfleetHistory').value          = notesOnfleet;
                document.getElementById('latitudeLongitude').value            = latitudeLongitude[1] +', '+ latitudeLongitude[0];
                document.getElementById('tableHistoryPackageTbody').innerHTML = '';
                document.getElementById('actualStatus').value                 = actualStatus;

                if(packageDelete)
                {
                    document.getElementById('divActualStatus').style.display = 'block';
                }
                else
                {
                    document.getElementById('actualStatus').style.display = 'block';
                }

                if(packageDispatch)
                {
                    document.getElementById('taskOnfleetHistory').value = packageDispatch.taskOnfleet;
                }

                let tableHistoryPackage = document.getElementById('tableHistoryPackageTbody');

                let tr = '';

                if(packageBlocked)
                {
                    tr =    '<tr>'+
                                '<td></td>'+
                                '<td>'+ packageBlocked.created_at.substring(5, 7) +'-'+ packageBlocked.created_at.substring(8, 10) +'-'+ packageBlocked.created_at.substring(0, 4) +'</td>'+
                                '<td>PACKAGE BLOCKED</td>'+
                                '<td>'+ packageBlocked.comment +'</td>'+
                            '</tr>';

                    tableHistoryPackage.insertRow(-1).innerHTML = tr;
                }
                if(packagesweights){
                document.getElementById('lengthSearch').value = packagesweights.length2;
                document.getElementById('heightSearch').value = packagesweights.height2;
                document.getElementById('widthSearch').value  = packagesweights.width2;
                document.getElementById('volumeSearch').value = '';
                document.getElementById('contactWeight').value = packagesweights.weight4;
                }
                packageHistoryList.forEach( package =>  {


                    let Description        = '';
                    let Description_Return = '';
                    let user               = (package.user ? package.user.name +' '+ package.user.nameOfOwner : '');
                    let idCellar           = package.idCellar;
                    let nameCellar     = package.nameCellar;
                    let stateCellar    = package.stateCellar;
                    let cityCellar     = package.cityCellar;

                    if(package.status == 'Dispatch' || package.status == 'Warehouse' || package.status == 'NMI')
                    {
                        if(idCellar > 0)
                        {
                            Description_Return = `<br><b class="text-warning">Warehouse (${nameCellar}):  ${cityCellar}, ${stateCellar}</b>`;
                        }

                        Description_Return = (package.Description_Return ? '<br><b class="text-danger">'+ package.Description_Return +'</b>' : '');
                        Description = package.Description;
                    }
                    else if(package.Description_Return != '' && package.Description_Return != null)
                    {
                        Description_Return = '<br><b class="text-danger">'+ package.Description_Return +'</b>';
                    }

                    if(package.status == 'Inbound')
                    {

                        if(package.Notes)
                        {
                            let dimensions = package.Notes.split('|');

                            if(dimensions.length == 3)
                            {
                                document.getElementById('lengthSearch').value = dimensions[0];
                                document.getElementById('heightSearch').value = dimensions[1];
                                document.getElementById('widthSearch').value  = dimensions[2];
                                document.getElementById('volumeSearch').value = (parseFloat(dimensions[0]) * parseFloat(dimensions[1]) * parseFloat(dimensions[2])).toFixed(2);
                            }
                        }
                        if(idCellar > 0)
                        {
                            Description_Return = `<br><b class="text-warning">Warehouse (${nameCellar}):  ${cityCellar}, ${stateCellar}</b>`;
                        }

                        Description = package.Description;
                    }
                    else if(package.status == 'Delivery')
                    {
                        Description = package.Description;
                        user        = (package.driver ? package.driver.name +' '+ package.driver.nameOfOwner : '');
                    }
                    else if(package.status == 'Failed')
                    {
                        Description = package.Description_Onfleet;
                    }
                    else
                    {
                        Description = package.Description;
                    }

                    let actualDate = (package.actualDate != null ? package.actualDate :  package.created_at);

                    tr =    '<tr>'+
                                '<td><b>'+ actualDate.substring(5, 7) +'-'+ actualDate.substring(8, 10) +'-'+ actualDate.substring(0, 4) +'</b><br>'+ actualDate.substring(11, 19) +
                                '<td><b>'+ package.created_at.substring(5, 7) +'-'+ package.created_at.substring(8, 10) +'-'+ package.created_at.substring(0, 4) +'</b><br>'+ package.created_at.substring(11, 19) +
                                '</td>'+
                                '<td>'+ user +'</td>'+
                                '<td>'+ package.status +'</td>'+
                                '<td>'+ Description + Description_Return +'</td>'+
                            '</tr>';

                    tableHistoryPackage.insertRow(-1).innerHTML = tr;
                });

                if(packageDispatch)
                {
                    if(packageDispatch.filePhoto1 == '' && packageDispatch.filePhoto2 == '')
                    {
                        if(packageDispatch.idOnfleet && packageDispatch.photoUrl)
                        {
                            let urlsPhoto = packageDispatch.photoUrl.includes('https:')

                            console.log('https: '+ urlsPhoto);
                            console.log(packageDispatch.photoUrl);

                            if(urlsPhoto)
                            {
                                urlsPhoto = packageDispatch.photoUrl.split(',');

                                urlsPhoto.forEach( url => {

                                    if(url)
                                    {
                                        tr =    '<tr>'+
                                                    '<td colspan="5" class="text-center"><img src="'+ url +'" class="img-fluid"/></td>'+
                                                '</tr>';

                                        tableHistoryPackage.insertRow(-1).innerHTML = tr;
                                    }
                                });
                            }
                            else
                            {
                                urlsPhoto = packageDispatch.photoUrl.split(',')

                                urlsPhoto.forEach( photoCode => {

                                    let urlOnfleetPhoto = 'https://d15p8tr8p0vffz.cloudfront.net/'+ photoCode +'/800x.png';

                                    tr =    '<tr>'+
                                                '<td colspan="5" class="text-center"><img src="'+ urlOnfleetPhoto +'" class="img-fluid"/></td>'+
                                            '</tr>';

                                    tableHistoryPackage.insertRow(-1).innerHTML = tr;
                                });
                            }
                        }
                        else if(packageDispatch.idOnfleet == '' && packageDispatch.photoUrl)
                        {
                            let urlsPhoto = packageDispatch.photoUrl.includes('https:')

                            console.log('https: '+ urlsPhoto);
                            console.log(packageDispatch.photoUrl);

                            if(urlsPhoto)
                            {
                                urlsPhoto = packageDispatch.photoUrl.split(',');

                                urlsPhoto.forEach( url => {

                                    if(url)
                                    {
                                        tr =    '<tr>'+
                                                    '<td colspan="5" class="text-center"><img src="'+ url +'" class="img-fluid"/></td>'+
                                                '</tr>';

                                        tableHistoryPackage.insertRow(-1).innerHTML = tr;
                                    }
                                });
                            }
                            else
                            {
                                urlsPhoto = packageDispatch.photoUrl.split(',')

                                urlsPhoto.forEach( photoCode => {

                                    let urlOnfleetPhoto = 'https://d15p8tr8p0vffz.cloudfront.net/'+ photoCode +'/800x.png';

                                    tr =    '<tr>'+
                                                '<td colspan="5" class="text-center"><img src="'+ urlOnfleetPhoto +'" class="img-fluid"/></td>'+
                                            '</tr>';

                                    tableHistoryPackage.insertRow(-1).innerHTML = tr;
                                });
                            }
                        }
                        else if(packageDelivery)
                        {
                            let urlsPhoto = packageDelivery.photoUrl.split('https:')

                            urlsPhoto.forEach( url => {

                                if(url)
                                {
                                    tr =    '<tr>'+
                                                '<td colspan="5" class="text-center"><img src="'+ url +'" class="img-fluid"/></td>'+
                                            '</tr>';

                                    tableHistoryPackage.insertRow(-1).innerHTML = tr;
                                }

                            });
                        }
                    }
                    else
                    {
                        if(packageDispatch.filePhoto1 != '')
                        {
                            let trPhoto1 =  '<tr>'+
                                                '<td colspan="5" class="text-center"><img src="'+ url_general +'img/deliveries/'+ packageDispatch.filePhoto1 +'" class="img-fluid"/></td>'+
                                            '</tr>';

                            tableHistoryPackage.insertRow(-1).innerHTML = trPhoto1;
                        }

                        if(packageDispatch.filePhoto2 != '')
                        {
                            let trPhoto2 =  '<tr>'+
                                                '<td colspan="5" class="text-center"><img src="'+ url_general +'img/deliveries/'+ packageDispatch.filePhoto2 +'" class="img-fluid"/></td>'+
                                            '</tr>';

                            tableHistoryPackage.insertRow(-1).innerHTML = trPhoto2;
                        }
                    }
                }


                document.getElementById('titleModalHistory').innerHTML = 'History Package: '+ PACKAGE_ID;
                document.getElementById('contactName').value           = '';
                document.getElementById('contactPhone').value          = '';
                document.getElementById('contactAddress').value        = '';
                document.getElementById('contactAddress2').value       = '';
                document.getElementById('highPriority').value          = 'Normal';

                if(packageHistoryList.length > 0)
                {
                    document.getElementById('idPackage').value       = packageHistoryList[0].Reference_Number_1;
                    document.getElementById('contactName').value     = packageHistoryList[0].Dropoff_Contact_Name;
                    document.getElementById('contactPhone').value    = packageHistoryList[0].Dropoff_Contact_Phone_Number;
                    document.getElementById('contactAddress').value  = packageHistoryList[0].Dropoff_Address_Line_1;
                    document.getElementById('contactAddress2').value = packageHistoryList[0].Dropoff_Address_Line_2;
                    document.getElementById('contactCity').value     = packageHistoryList[0].Dropoff_City;
                    document.getElementById('contactState').value    = packageHistoryList[0].Dropoff_Province;
                    document.getElementById('contactZipCode').value  = packageHistoryList[0].Dropoff_Postal_Code;
                    document.getElementById('contactRoute').value    = packageHistoryList[0].Route;
                    document.getElementById('internalComment').value = packageHistoryList[0].internal_comment;
                    document.getElementById('highPriority').value    = packageHistoryList[0].highPriority;
                }

                if(searchGlobal == 1)
                    $('#btnBackGoTo').css('display', 'block');
                else
                    $('#btnBackGoTo').css('display', 'none');

                $('#exampleModal').modal('toggle');
            });
        }

        function SearchPackageTask(e)
        {
            e.preventDefault();

            let PACKAGE_ID = document.getElementById('searchPackageTask').value;

            fetch(url_general +'package-history/search-task/'+ PACKAGE_ID)
            .then(response => response.json())
            .then(response => {

                if(response.stateAction == 200)
                {
                    let onfleet = response.onfleet;
                    let driver  = response.driver;
                    let team    = response.team;

                    document.getElementById('taskOnfleet').value = document.getElementById('searchPackageTask').value;
                    document.getElementById('packageID').value = onfleet['notes'];
                    document.getElementById('teamOnfleet').value   = team;
                    document.getElementById('driverOnfleet').value = driver;

                    document.getElementById('contactOnfleetName').value    = (onfleet['recipients'].length > 0 ? onfleet['recipients'][0]['name'] : '') ;
                    document.getElementById('contactOnfleetPhone').value   = (onfleet['recipients'].length > 0 ? onfleet['recipients'][0]['phone'] : '') ;
                    document.getElementById('contactOnfleetAddress').value = onfleet['destination']['address']['apartment'] +' '+ onfleet['destination']['address']['country'] +' '+ onfleet['destination']['address']['number'] +' '+ onfleet['destination']['address']['postalCode'] +' '+ onfleet['destination']['address']['street'];
                    document.getElementById('contactOnfleetCity').value    = onfleet['destination']['address']['city'];
                    document.getElementById('contactOnfleetState').value   = onfleet['destination']['address']['state'];
                    document.getElementById('contactOnfleetZipCode').value = onfleet['destination']['address']['postalCode'];
                    document.getElementById('statusOnfleet').value  = onfleet['state'] +' success('+ onfleet['completionDetails']['success'] +')' ;

                    let tr = '';

                    if(onfleet['state'] == 3)
                    {
                        document.getElementById('tableOnfleetTbody').innerHTML = '';

                        let tableOnfleet = document.getElementById('tableOnfleet');

                        if(onfleet['completionDetails']['photoUploadIds'].length > 1)
                        {
                            let urlsPhoto = onfleet['completionDetails']['photoUploadIds']

                            urlsPhoto.forEach( photoUploadId => {

                                tr =    '<tr>'+
                                            '<td colspan="3"><img src="https://d15p8tr8p0vffz.cloudfront.net/'+ photoUploadId +'/800x.png" class="img-fluid"/></td>'+
                                        '</tr>';

                                tableOnfleet.insertRow(-1).innerHTML = tr;
                            });
                        }
                        else
                        {
                            let photoUploadId = onfleet['completionDetails']['photoUploadId'];

                            tr =    '<tr>'+
                                        '<td colspan="3"><img src="https://d15p8tr8p0vffz.cloudfront.net/'+ photoUploadId +'/800x.png" class="img-fluid"/></td>'+
                                    '</tr>';

                            tableOnfleet.insertRow(-1).innerHTML = tr;
                        }
                    }

                    $('#exampleModalTask').modal('toggle');
                }
                else
                {
                    alert('error consulta data');
                }
            });
        }

        function SaveEditPackageHistory(e)
        {
            e.preventDefault();

            let PACKAGE_ID = document.getElementById('searchPackage').value;

            let formData = new FormData();

            formData.append('Reference_Number_1', document.getElementById('idPackage').value);
            formData.append('Dropoff_Contact_Name', document.getElementById('contactName').value);
            formData.append('Dropoff_Contact_Phone_Number', document.getElementById('contactPhone').value);
            formData.append('Dropoff_Address_Line_1', document.getElementById('contactAddress').value);
            formData.append('Dropoff_Address_Line_2', document.getElementById('contactAddress2').value);
            formData.append('Dropoff_City', document.getElementById('contactCity').value);
            formData.append('Dropoff_Province', document.getElementById('contactState').value);
            formData.append('Dropoff_Postal_Code', document.getElementById('contactZipCode').value);
            formData.append('Weight', document.getElementById('contactWeight').value);
            formData.append('Route', document.getElementById('contactRoute').value);
            formData.append('internal_comment', document.getElementById('internalComment').value);
            formData.append('highPriority', document.getElementById('highPriority').value);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'package-history/update',

                {
                    headers: { "X-CSRF-TOKEN": token },
                    method: 'post',
                    body: formData
                }
            )
            .then(response => response.json())
            .then(response => {

                if(response.stateAction == true)
                {
                    swal('Correct!', 'The package was updated', 'success');
                }
                else if(response.stateAction == 'notUpdated')
                {
                    if(response.response)
                    {
                        swal({
                            title: "Package not updated!",
                            text: response.response.error +"!",
                            icon: "error",
                            button: "Ok!",
                        });
                    }
                    else
                    {
                        swal({
                            title: "The package not exists in INLAND!",
                            icon: "warning",
                            button: "Ok!",
                        });
                    }
                }
                else
                {
                    swal('Error!', 'The package was not updated', 'success');
                }
            });
        }

        function SearchPackageByFilters(e)
        {
            e.preventDefault();

            let PACKAGE_ID = document.getElementById('searchPackage').value;

            let formData = new FormData();

            formData.append('Dropoff_Contact_Name', document.getElementById('clientNameFilters').value);
            formData.append('Dropoff_Contact_Phone_Number', document.getElementById('contactPhoneFilters').value);
            formData.append('Dropoff_Address_Line_1', document.getElementById('contactAddressFilters').value);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'package-history/search-by-filters',

                {
                    headers: { "X-CSRF-TOKEN": token },
                    method: 'post',
                    body: formData
                }
            )
            .then(response => response.json())
            .then(response => {

                let packageHistoryList = response.packageHistoryList;

                document.getElementById('tableListPackageByFiltersBody').innerHTML = '';

                let tableHistoryPackage = document.getElementById('tableListPackageByFiltersBody');

                let tr = '';

                if(packageHistoryList.length > 0)
                {
                    packageHistoryList.forEach(packageHistory => {

                        let Reference_Number_1 = "'"+ packageHistory.Reference_Number_1 +"'";

                        tr =    '<tr>'+
                                    '<td>'+ packageHistory.created_at.substring(5, 7) +'-'+ packageHistory.created_at.substring(8, 10) +'-'+ packageHistory.created_at.substring(0, 4) +'</td>'+
                                    '<td>'+ packageHistory.company +'</td>'+
                                    '<td class="text-primary pointer" onclick="SearchByPackageID('+ Reference_Number_1 +')">'+ packageHistory.Reference_Number_1 +'</td>'+
                                    '<td>'+ packageHistory.status +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_Contact_Name +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_Contact_Phone_Number +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_Address_Line_1 +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_City +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_Province +'</td>'+
                                    '<td>'+ packageHistory.Dropoff_Postal_Code +'</td>'+
                                    '<td>'+ packageHistory.Route +'</td>'+
                                '</tr>';

                        tableHistoryPackage.insertRow(-1).innerHTML = tr;
                    });
                }
                else
                {
                    swal('Atention!', 'There are no packages for the entered filters', 'warning');
                }
            });
        }

        function SearchByPackageID(Reference_Number_1)
        {
            document.getElementById('searchPackage').value = Reference_Number_1;

            $('#searchByFiltersModal').modal('toggle');

            SearchPackageReferenceId();
        }

        let searchGlobal = 0;

        function ViewModalSearchFilters()
        {
            searchGlobal = 1;

                $('#exampleModal').modal('hide');
                $('#searchByFiltersModal').modal('toggle');
                $('#searchByFiltersModal').modal('show').on('shown.bs.modal', function () {
                $(this).css('overflow-y', 'auto');
                });
        }

        function CloseModal(idModal)
        {
            $('#'+ idModal).modal('toggle');

            document.getElementById('bodyAdmin').setAttribute('style', 'position: relative; min-height: 100%; top: 0px;');
        }
    </script>

    <?php
        $dateFileApp = date("Y-m-d-H-i-s", filemtime("./js/app.js"));
    ?>

    <script src="{{ asset('js/app.js') }}?{{$dateFileApp}}"></script>
</body>
</html>
