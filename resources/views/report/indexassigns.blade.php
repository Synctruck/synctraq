@extends('layout.admin')
@section('title', 'Reportes - Assigns Teams')
@section('content')
<div class="pagetitle">
  	<h1>Reportes - Assigns Teams</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">Reportes Assigns Teams</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-01')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="reportAssigns">
</div>
@endsection