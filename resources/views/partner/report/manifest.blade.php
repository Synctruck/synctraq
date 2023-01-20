@extends('layout.partner')
@section('title', 'REPORTS -  Manifest')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  MANIFEST</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Manifest</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="reportPartnerManifest">
</div>
@endsection
