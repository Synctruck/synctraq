@extends('layout.admin')
@section('title', 'RETURN COMPANY')
@section('content')
<div class="pagetitle">
  	<h1>RETURN COMPANY</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">REPORTS</a></li>
			<li class="breadcrumb-item active">RETURN COMPANY</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Session::get('user')->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
</script>
<div id="reportReturnCompany">
</div>
@endsection
