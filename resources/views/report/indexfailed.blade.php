@extends('layout.admin')
@section('title', 'REPORTS -  Failed')
@section('content')
<div class="pagetitle">
  	<h1><b>REPORT - FAILED</b></h1>
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Reports Failed</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let idUserGeneral = '{{Auth::user()->id}}';
	let auxDateInit   = '{{date('Y-m-d')}}';
	let auxDateEnd    = '{{date('Y-m-t')}}';
    let auth = @json(Auth::user());
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
<div id="reportFailed">
</div>
@endsection