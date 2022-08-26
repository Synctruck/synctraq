@extends('layout.admin')
@section('title', 'Deliveries Validation')
@section('content')
<div class="pagetitle">
  	<h1>Deliveries Validation</h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Processes</a></li>
			<li class="breadcrumb-item active">Deliveries</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateInit = '{{date('Y-m-01')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="delivery">
</div>
@endsection
