@extends('layout.admin')
@section('title', 'Reports - All')
@section('content')
<div class="pagetitle">
  	<h1>Roles</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Roles</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="roles">
</div>
@endsection
