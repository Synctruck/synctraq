@extends('layout.admin')
@section('title', 'Package - High- Priority')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGE - HIGH - PRIORITY</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Package High Priority</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="packageHighPriority">
</div>
@endsection