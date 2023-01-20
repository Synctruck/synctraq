@extends('layout.partner')
@section('title', 'REPORTS -  Assigns Teams')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  ASSIGNS TEAMS</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Assigns Teams</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="partnerAssigns">
</div>
@endsection
