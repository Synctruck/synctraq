@extends('layout.admin')
@section('title', 'Inventory Tool')
@section('content')
<div class="pagetitle">
  	<h1><b>INVENTORY TOOL</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item"><a href="#">Warehouse</a></li>
			<li class="breadcrumb-item active">Inventory Tool</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let dateGeneral = '{{ date('d/m/Y') }}';
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="inventoryTool">
</div>
@endsection