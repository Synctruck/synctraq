@extends('layout.admin')
@section('title', 'Dashboard')
@section('content')
<div class="pagetitle">
  	<h1><b>DASHBOARD</b></h1>}
   {{ Auth::user()->idTeam }}
  	<nav>
    	<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="#">Home</a></li>
			<li class="breadcrumb-item active">Dashboard</li>
    	</ol>
  	</nav>
</div><!-- End Page Title -->
<script>
	let auxDateStart = '{{date('Y-m-d')}}';
	let auxDateEnd  = '{{date('Y-m-t')}}';
    let idRoleTeamGeneral = 3;
    let idRoleDriverGeneral = 0;
    let idUserGeneral = '{{Auth::user()->id}}';

    /*if(auth()->role == 'Team'){
       idRoleTeamGeneral=3;}
       idTeamGeneral = idUserGeneral
    }

    if(auth()->role == 'Driver' ){
        idRoleTeamGeneral = 3;
        idRoleDriverGeneral = 4;
        idTeamGeneral = '{{Auth::user()->idTeam}}'
        idDriverGeneral = idUserGeneral
    }*/
    console.log(idUserGeneral);
</script>
<div id="dashboardDeliveries"> 
</div>
@endsection
