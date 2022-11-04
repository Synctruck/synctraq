<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ORDER | PRINT</title>
</head>
<style>
	ul {
    	list-style: none;
	}
	li::before {
	    display: inline-block;
	    content: '-';
	    margin-right: 0.2rem;
	}
	li {
	    text-indent: -0.75em;
	}
</style>
<body>
	<div style="width: 100%; text-align:center;">
		<img src="./img/cookies.png" width="250">
		<br>
	</div>
	<div style="width: 100%; text-align:center;">
		<u>Chip City Cookies</u>
		<br><br>
	</div>
	<div style="width: 100%; margin-top: 100px, border: 1px solid black;">
		<table border="1" style="width: 100%;">
			<tr>
				<td colspan="4" style="text-align: center;">
					<div style="float: left; width: 58%;">
						Pick Up Barcode:
					</div>
					<div style="float: left; width: 42%; text-align: center;">
						<br>
						{!! DNS1D::getBarcodeHTML($orderList[0]->Reference_Number_1, 'C128', 2, 60) !!}
						{{ $orderList[0]->Reference_Number_1 }}
					</div>
					<br><br><br><br><br>
				</td>
			</tr>
			<tr>
				<td colspan="2"><u><b>Pick up Location</b></u></td>
				<td colspan="2">Quantity of Boxes: {{ $orderList[0]->quantity }}</td>
			</tr>
			<tr>
				<td>Location:</td>
				<td colspan="3">{{ $orderList[0]->Dropoff_City }}</td>
			</tr>
			<tr>
				<td>Address:</td>
				<td colspan="3">{{ $orderList[0]->Dropoff_Address_Line_1 }}</td>
			</tr>
			<tr>
				<td>Pick Up Date:</td>
				<td>{{ date('m-d-Y', strtotime($orderList[0]->created_at)) }}</td>
				<td>Pick Up Time:</td>
				<td>{{ date('H:i:s', strtotime($orderList[0]->created_at)) }}</td>
			</tr>
		</table>
	</div>
	<br><br>
	<div style="width: 100%; margin-top: 100px, border: 1px solid black;">
		<table border="1" style="width: 100%;">
			<tr>
				<td><u><b>Drop Off Location</b></u></td>
				<td></td>
				<td colspan="2"><u><b>Promise Time</b></u></td>
			</tr>

			<tr>
				<td>Customer Name:</td>
				<td colspan="3">{{ $orderList[1]->Dropoff_Contact_Name }}</td>
			</tr>
			<tr>
				<td>Address:</td>
				<td colspan="3">{{ $orderList[1]->Dropoff_Address_Line_1 }}</td>
			</tr>
			<tr>
				<td>Phone Number:</td>
				<td>{{ $orderList[1]->Dropoff_Contact_Phone_Number }}</td>
				<td colspan="2">
					<div style="float: left; width: 25%;">
						Drop Off Barcode:
					</div>
					<div style="float: left; width: 75%; text-align: center;">
						<br>
						{!! DNS1D::getBarcodeHTML($orderList[1]->Reference_Number_1, 'C128', 2, 60) !!}
						{{ $orderList[1]->Reference_Number_1 }}
					</div>
					<br><br><br><br><br>
				</td>
			</tr>
		</table>
	</div>
	<br><br>
	<div style="width: 100%; margin-top: 100px, border: 1px solid black;">
		<table style="width: 100%;">
			<tr>
				<td style="padding: 5px 0px 5px 120px;">
					<ul>
						<li>Order are fragile. Please treat with care.</li>
						<li>Promise time are not suggestions. We must meet the delivery window.</li>
						<li>Item are perisable, do not leave unattended in hot vehicles.</li>
						<li>Please make sure to use both barcodes when completing these stops.</li>
					</ul>
				</td>
			</tr>
		</table>
	</div>
	<br><br><br><br>
	<div style="width: 100%; margin-top: 100px, border: 1px solid black;">
		<table style="width: 100%;">
			<tr>
				<td style="text-align: center;">
					_____________________________________________ <br>
					Customer Signature
				</td>
			</tr>
		</table>
	</div>
</body>
</html>