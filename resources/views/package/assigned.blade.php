@extends('layout.admin')
@section('title', 'Package - Assigned')
@section('content')
<div class="pagetitle">
  	<h1>Package - Assigned</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Assigned</li>
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
</script>
<div id="assigned">
</div>
@endsection