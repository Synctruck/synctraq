@extends('layout.admin')
@section('title', 'Reportes - Dispatch')
@section('content')
<div class="pagetitle">
  	<h1>Reportes - Dispatch</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">Reportes Dispatch</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="reportDispatch">
</div>
@endsection
