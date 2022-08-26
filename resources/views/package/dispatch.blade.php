@extends('layout.admin')
@section('title', 'Packages - Dispatch')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - DISPATCH</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Packages</li>
    	</ol>
    	<?php

			$routesTeamGeneral = '';

		?>

    	@if(count(Session::get('user')->routes_team) > 0)
    		@foreach(Session::get('user')->routes_team as $routeTeam)
    			<?php

    				$routesTeamGeneral = $routesTeamGeneral == '' ? $routeTeam->route->name : $routesTeamGeneral .'|'. $routeTeam->route->name;
    			?>
    		@endforeach
    	@endif
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral     = '{{Session::get('user')->id}}';
	let routesTeamGeneral = '{{$routesTeamGeneral}}';
</script>
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="packageDispatch">
</div>
@endsection
