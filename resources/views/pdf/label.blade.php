<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Synctruck | Label - {{$packageInbound->Reference_Number_1}}</title>
</head>
<style>
	.verticalText
	{
		writing-mode: vertical-lr;
		transform: rotate(270deg);
	}
</style>
<body style="font-family: sans-serif;">
	  
	@php
	    $generatorPNG = new Picqer\Barcode\BarcodeGeneratorPNG();
	@endphp
	  
	<table>
		<tr>
			<td style="width: 72px;">
				<h1 class="verticalText" style="font-size: 35px;">EWR1</h1>
			</td>
			<td style="width: 370px">
				<table style="width: 100%;">
					<tr>
						<td style="width: 100%; text-align: center;">
							<h1 style="letter-spacing: 5px; font-size: 40px; margin-bottom: 0px;" >
								{{$packageInbound->Weight}} - {{$packageInbound->Dropoff_Province}} - {{$packageInbound->Route}}
							</h1>
						</td>
					</tr>
					<tr>
						<td style="width: 100%; text-align: center; margin-bottom: 0px;">
							<img src="data:image/png;base64,{{ base64_encode($generatorPNG->getBarcode($packageInbound->Reference_Number_1, $generatorPNG::TYPE_CODE_128)) }}" style="height: 50px; width: 90%; margin-bottom: 0px;">
						</td>
					</tr>
					<tr>
						<td style="width: 100%; text-align: center;">
							<h3 style="margin-top: 0px;">{{$packageInbound->Reference_Number_1}}</h3>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>