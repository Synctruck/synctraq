@extends('layout.admin')
@section('title', 'Debrief')
@section('content')
<div class="pagetitle">
  	<h1><b>DEBRIEF</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Debrief</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
</script>
<div id="debrief">
</div>
@endsection