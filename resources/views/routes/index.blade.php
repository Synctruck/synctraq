@extends('layout.admin')
@section('title', 'Route Maintenance')
@section('content')
<div class="pagetitle">
  	<h1><b>ROUTE MAINTENANCE W</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Routes</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<div>
<form id="" name="" method="post" action="{{url('upload-live-routes')}}" enctype="multipart/form-data">
	@csrf
    <input type="file" name="file" onchange="this.form.submit()" /> 
</form>
</div>
<div id="routes">
</div>
@endsection
