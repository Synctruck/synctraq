<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>PRE FACTURA | SYNCTRUCK</title>
</head>
<body>
	<h1>PRE INVOICES ATTACHED</h1>
	<h3>START DATE: {{$data['startDate']}}</h3>
	<h3>END DATE: {{$data['endDate']}}</h3>
	<p>To confirm the invoice go to the <a href="{{url('charge-company/pre-confirm')}}/{{$data['startDate']}}/{{$data['endDate']}}">following link</a></p>
</body>
</html>