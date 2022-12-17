<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Synctruck | PALLET - {{ $pallet->number }}</title>
</head>
<style>
</style>
<body style="font-family: sans-serif;">
	  
	@php
	    $generatorPNG = new Picqer\Barcode\BarcodeGeneratorPNG();
	@endphp
	  
	<table style="width: 50%; text-align: center;">
		<tr> 
			<td style="width: 100px; text-align: center;">
				<h1 class="verticalText" style="font-size: 35px; text-align: center;">{{ $pallet->number }}</h1>
				<img src="data:image/png;base64,{{ base64_encode($generatorPNG->getBarcode($pallet->number, $generatorPNG::TYPE_CODE_128)) }}" style="height: 50px; width: 100%; margin-bottom: 0px;">
			</td>
		</tr>
	</table>
</body>
</html>