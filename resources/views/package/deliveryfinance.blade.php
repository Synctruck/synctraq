@extends('layout.admin')
@section('title', 'PACKAGE - DELIVERY FINANCE')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGE - DELIVERY FINANCE</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Package Delivery Finance</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="packageFinance">
</div>
@endsection
