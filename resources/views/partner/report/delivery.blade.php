@extends('layout.partner')
@section('title', 'REPORTS -  Delivery')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  DELIVERY</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Delivery</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
    var id_team	    = 0;
    var id_driver   = 0;
</script>
<div id="reportPartnerDelivery">
</div>
@endsection
