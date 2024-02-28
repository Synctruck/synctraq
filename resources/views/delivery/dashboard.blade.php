@extends('layout.admin')
@section('title', 'Dashboard')
@section('content')
<div class="pagetitle">
  	<h1><b>DASHBOARD</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Dashboard</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateStart = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
	let idTeamGeneral = 0;
	let idDriverGeneral = 0;
	let idRoleTeamGeneral = 0;
	let idRoleDriverGeneral = 0;

	if(auth()->role == 'Team')
	{
		idRoleTeamGeneral = 3;
	}

	if(auth()->role == 'Driver')
	{
		idRoleTeamGeneral = 3
		idRoleDriverGeneral = 4;
	}
</script>
<div id="dashboardDeliveries">
</div>
@endsection