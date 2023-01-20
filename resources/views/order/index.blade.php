@extends('layout.admin')
@section('title', 'ORDERS')
@section('content')
<div class="pagetitle">
  	<h1><b>ORDERS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Orders</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-01')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="orders">
</div>
@endsection
