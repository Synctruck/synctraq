@extends('layout.admin')
@section('title', 'Package - Unassigned')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGE - UNASSIGNED</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Unassigned</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral     = '{{Auth::user()->id}}';
</script>
<div id="unassigned">
</div>
@endsection
