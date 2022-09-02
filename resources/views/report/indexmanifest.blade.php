@extends('layout.admin')
@section('title', 'Reportes - Manifest')
@section('content')
<div class="pagetitle">
  	<h1>Reportes - Manifest</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">Reportes Manifest</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="reportManifest">
</div>
@endsection
