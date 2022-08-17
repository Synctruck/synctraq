@extends('layout.admin')
@section('title', 'Driver Maintenance')
@section('content')
<div class="pagetitle">
  	<h1>Driver Maintenance</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Drivers</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Session::get('user')->id}}';
</script>
<div id="driver">
</div>
@endsection 