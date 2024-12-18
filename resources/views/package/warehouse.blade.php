@extends('layout.admin')
@section('title', 'Packages - Warehouse')
@section('content')
<div class="pagetitle">
  	<h1><b>PACKAGES - WAREHOUSE</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Packages</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	var validateInventoryTool = 'none'
</script>
@if(hasPermission('inventory-tool.index'))
	<script>
		var validateInventoryTool = 'block'
	</script>
@endif
<script>
	let auxDateInit = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
</script>
<div id="packageWarehouse">
</div>
@endsection