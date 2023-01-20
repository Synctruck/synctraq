@extends('layout.admin')
@section('title', 'REPORTS -  Not Exists')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  NOT EXISTS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Not Exists</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="reportNotExists">
</div>
@endsection
