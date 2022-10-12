@extends('layout.partner')
@section('title', 'REPORTS -  Delivery')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORTS -  DELIVERY</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Delivery</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::guard('partner')->user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
    let auth = @json(Auth::guard('partner')->user());
    var id_team = 0;
    var id_driver = 0;
        if(auth.idRole == 4){
            id_team = auth.idTeam
            id_driver = auth.id;
        }
        if(auth.idRole == 3){
            id_team = auth.id
        }
        console.log(id_team,id_driver)

</script>
<div id="reportPartnerDelivery">
</div>
@endsection
