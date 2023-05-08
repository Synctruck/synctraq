@extends('layout.admin')
@section('title', 'DISPATCH DRIVER | SYNCTRUCK')
@section('content')
<div class="pagetitle">
  	<h1><b>DISPATCH - DRIVER</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Packages</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit 	  = '{{ date('Y-m-d') }}';
	let auxDateEnd  	  = '{{ date('Y-m-t') }}';
	let userGeneral       = '{{ Auth::user() }}';
	let idUserGeneral     = '{{ Auth::user()->id }}';
	let idTeamGeneral     = '{{ Auth::user()->idTeam }}';
	let roleGeneral 	  = '{{ Auth::user()->role->name }}';
</script>
<div id="packageDispatchDriver">
</div>
@endsection