<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>DRAFT INVOICES | SYNCTRUCK</title>
</head>
<body>
	<h1>DRAFT INVOICES ATTACHED</h1>
	<h3>DELIVERY START DATE: {{$data['startDate']}}</h3>
	<h3>DELIVERY END DATE: {{$data['endDate']}}</h3>
	<p>To confirm the invoice go to the <a href="{{url('charge-company')}}">following link</a></p>
</body>
</html>