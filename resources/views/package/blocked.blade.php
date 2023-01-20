@extends('layout.admin')
@section('title', 'Packages - Blocked')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - BLOCKED</b></h1>
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
<div id="packageBlocked">
</div>
<style>
	.swal-title
	{
		font-size: 60px;
	}
</style>
@endsection
