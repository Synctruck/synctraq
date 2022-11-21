@extends('layout.admin')
@section('title', 'CHARGE - COMPANY')
@section('content')
<div class="pagetitle">
  	<h1><b>CHARGE - COMPANY</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Charge Company</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="chargeCompany">
</div>
@endsection