@extends('layout.admin')
@section('title', 'Packages - Check Stop')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - CHECK STOP</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Packages</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-01')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="packageCheck">
</div>
@endsection
