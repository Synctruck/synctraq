@extends('layout.admin')
@section('title', 'Packages - Warehouse')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - MIDDLE MILE SCAN</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Packages</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="packageMiddleMileScan">
</div>
@endsection