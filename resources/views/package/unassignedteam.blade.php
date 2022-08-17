@extends('layout.admin')
@section('title', 'Package - Unassigned')
@section('content')
<div class="pagetitle">
  	<h1>Package - Unassigned</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Unassigned</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral     = '{{Session::get('user')->id}}';
</script>
<div id="unassignedTeam">
</div>
@endsection