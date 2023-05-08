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

    	@if(count(Auth::user()->routes_team) > 0)
    		@foreach(Auth::user()->routes_team as $routeTeam)
    			<?php

    				$routesTeamGeneral = $routesTeamGeneral == '' ? $routeTeam->route->name : $routesTeamGeneral .'|'. $routeTeam->route->name;
    			?>
    		@endforeach
    	@endif
  	</nav>
</div><!-- End Page Title -->

<script>
	let userGeneral       = '{{Auth::user()}}';
	let idUserGeneral     = '{{Auth::user()->id}}';
	let idTeamGeneral     = '{{Auth::user()->idTeam}}';
	let routesTeamGeneral = '{{$routesTeamGeneral}}';
	let roleGeneral 	  = '{{ Auth::user()->role->name }}';
</script>
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="packageDispatch">
</div>
@endsection