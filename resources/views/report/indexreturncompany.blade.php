@extends('layout.admin')
@section('title', 'RETURN COMPANY')
@section('content')
<div class="pagetitle">
  	<h1><b>RETURN COMPANY</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Reports</a></li>
			<li class="breadcrumb-item active">Return Company</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="reportReturnCompany">
</div>
@endsection
