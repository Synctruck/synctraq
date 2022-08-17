@extends('layout.admin')
@section('title', 'Report - Failed')
@section('content')
<div class="pagetitle">
  	<h1>Report - Failed</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">Report Failed</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Session::get('user')->id}}';
	let auxDateInit   = '{{date('Y-m-01')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="reportFailed">
</div>
@endsection