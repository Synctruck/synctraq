@extends('layout.admin')
@section('title', 'PACKAGE - DELIVERY CHECK')
@section('content')
<div class="pagetitle">
  	<h1>PACKAGE - DELIVERY CHECK</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Inicio</a></li>
			<li class="breadcrumb-item active">PACKAGE DELIVERY CHECK</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Session::get('user')->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="packageCheckDelivery">
</div>
@endsection