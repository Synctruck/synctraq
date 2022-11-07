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
</script>
<div id="dashboard">
</div>
@endsection