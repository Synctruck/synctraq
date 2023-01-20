@extends('layout.admin')
@section('title', 'Packages - Returns')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - RETURNS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Finance</a></li>
			<li class="breadcrumb-item active">Packages Returns</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-d')}}';
</script>
<div id="packageReturn">
</div>
@endsection
