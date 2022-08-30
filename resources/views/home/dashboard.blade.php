@extends('layout.admin')
@section('title', 'Dasboard')
@section('content')
<div class="pagetitle">
  	<h1>Dasboard</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">Dasboard</li>
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
