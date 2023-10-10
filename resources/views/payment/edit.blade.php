@extends('layout.admin')
@section('title', 'PAYMEN - TEAMS')
@section('content')
<div class="pagetitle">
</div><!-- End Page Title -->
<script>
	let idPaymentGeneral 	 = '{{ $payment->id }}';
	let idUserGeneral 	 	 = '{{Auth::user()->id}}';
	let numberTransactionGeneral = '{{ $payment->numberTransaction }}';
	let teamNameGeneral  	 = '{{ $payment->team->name }}';
	let paymentIdGeneral 	 = '{{ $payment->id }}';
	let paymentStatusGeneral = '{{ $payment->status }}';
	let startDateGeneral     = '{{ date('m/d/Y', strtotime($payment->startDate)) }}';
	let endDateGeneral 	     = '{{ date('m/d/Y', strtotime($payment->endDate)) }}';
</script>
<div id="paymentAdjustment"> 
</div>
@endsection