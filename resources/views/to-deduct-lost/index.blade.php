@extends('layout.admin')
@section('title', 'TO DEDUCT LOST PACKAGES')
@section('content')
<div class="pagetitle">
  	<h1><b>TO DEDUCT LOST PACKAGES</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">To Deduct Lost Packages</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="toDeductLostPackages"> 
</div>
@endsection