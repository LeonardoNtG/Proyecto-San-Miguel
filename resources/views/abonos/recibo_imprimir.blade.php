<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo N° #{{ $pago->id_abono }}</title>

    <link rel="stylesheet" href="{{ asset('css/recibo.css') }}">
</head>
<body onload="window.print()">
    <div class="recibo-container">
        
        <span class="recibo-data" id="data-numero-recibo">{{ $pago->id_abono }}</span>
        
        <span class="recibo-data" id="data-nombre-cliente">{{ $cliente->nombres_apellidos}}</span>
        <span class="recibo-data" id="data-nombre-cliente2">{{ $cliente->nombres_apellidos}}</span>

        <span class="recibo-data" id="data-pv-cliente">{{ $cliente->pv_num}}</span>
        
        <span class="recibo-data" id="data-monto-usd">{{ number_format($pago->monto_abonado, 2) }}</span>

        <span class="recibo-data" id="data-monto-letras">{{ $monto_en_letras }}</span>

        <span class="recibo-data" id="data-concepto">{{ $pago->tipo_pago }}</span>
        
        <span class="recibo-data" id="data-fecha-dia">{{ date('d', strtotime($pago->fecha_pago)) }}</span>
        <span class="recibo-data" id="data-fecha-mes">{{ date('M', strtotime($pago->fecha_pago)) }}</span>
        <span class="recibo-data" id="data-fecha-anio">{{ date('Y', strtotime($pago->fecha_pago)) }}</span>

    </div>
</body>
</html>