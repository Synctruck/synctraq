@extends('layout.admin')
@section('title', 'Package - Assigneds')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGE - ASSIGNEDS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Assigneds</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
</script>
<div id="assignedTeam">
</div>
@endsection
