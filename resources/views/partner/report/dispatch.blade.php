@extends('layout.partner')
@section('title', 'REPORTS -  Dispatch')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  DISPATCH</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Dispatch</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>

	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
    let auth = @json(Auth::user());
    var id_team = 0;
    var id_driver = 0;

</script>
<div id="reportPartnerDispatch">
</div>
@endsection
