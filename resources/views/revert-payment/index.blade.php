@extends('layout.admin')
@section('title', 'REVERTS - PAYMENTS')
@section('content')
<div class="pagetitle">
  	<h1><b>REVERTS - PAYMENTS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Revert Payment</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="reverts"> 
</div>
@endsection