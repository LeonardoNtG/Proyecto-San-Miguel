<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo N° {{ $pago->id_abono }}</title>
    <style>
        /* CSS Reset for Printing */
        @page {
            size: letter portrait; /* Changed to portrait as requested */
            margin: 0;
        }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            background-color: white;
        }
        * {
            box-sizing: inherit;
        }
        
        .page-container {
            width: 100%;
            max-width: 8.5in; /* Simulate US Letter Portrait width */
            margin: 0 auto; /* Center on screen */
            min-height: 3.8in; /* Base height, allows growing if content overflows */
            display: flex;
            justify-content: space-between;
            align-items: stretch; /* Make both cards the same height */
            padding: 15px 10px;
        }

        .receipt-card {
            border: 2px solid #1A237E; /* Deep Blue border */
            position: relative;
            padding: 15px 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Left receipt is larger */
        .receipt-card-left {
            width: 58%;
        }

        /* Right receipt is smaller */
        .receipt-card-right {
            width: 39%;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .logo-container {
            width: 80px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .logo-placeholder {
            width: 70px;
            height: 60px;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #999;
            text-align: center;
        }

        .company-info {
            text-align: center;
            flex-grow: 1;
            color: #1A237E; /* Deep blue */
            font-size: 11px;
            font-weight: bold;
            line-height: 1.2;
        }

        /* Make company info smaller in the right receipt */
        .receipt-card-right .company-info {
            font-size: 9px;
        }

        .receipt-number-container {
            width: 50px;
            text-align: right;
            color: #D32F2F; /* Red */
            font-weight: bold;
            font-size: 16px;
        }

        /* Title block */
        .title-block {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-right: 5px;
        }

        .title {
            color: #1A237E;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .receipt-card-right .title {
            font-size: 18px;
        }

        .amount-boxes-container {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .amount-box {
            display: flex;
            align-items: center;
            font-size: 11px;
            font-weight: bold;
            color: #1A237E;
        }
        
        .receipt-card-right .amount-box {
            font-size: 9px;
        }

        .amount-input {
            border: 2px solid #444;
            background-color: #e0e0e0;
            width: 70px;
            height: 20px;
            display: inline-block;
            margin-left: 5px;
            text-align: center;
            line-height: 18px;
            color: black;
            box-shadow: 2px 2px 0px #444;
        }
        
        .receipt-card-right .amount-input {
            width: 50px;
            height: 16px;
            line-height: 14px;
            font-size: 10px;
        }

        /* Row Layout */
        .row {
            display: flex;
            align-items: baseline;
            margin-bottom: 6px;
            font-size: 11px;
            color: #1A237E;
            font-weight: bold;
        }

        .receipt-card-right .row {
            font-size: 9px;
        }

        .row .label {
            white-space: nowrap;
            margin-right: 8px;
        }

        .row .value {
            flex-grow: 1;
            border-bottom: 1px solid #333;
            color: black;
            padding-left: 5px;
            font-size: 12px;
        }
        
        .receipt-card-right .row .value {
            font-size: 10px;
        }

        /* Small details row */
        .details-row {
            font-size: 9px;
            color: black;
            font-weight: bold;
            margin-bottom: 6px;
            margin-left: 2px;
        }

        .receipt-card-right .details-row {
            font-size: 8px;
        }
        
        /* Date Row */
        .date-row {
            display: flex;
            align-items: baseline;
            margin-top: 5px;
            margin-bottom: 5px;
            font-size: 11px;
            color: #1A237E;
            font-weight: bold;
        }

        .receipt-card-right .date-row {
            font-size: 9px;
        }

        .date-input {
            border-bottom: 1px solid #333;
            color: black;
            display: inline-block;
            text-align: center;
            min-width: 30px;
            margin: 0 5px;
            font-size: 12px;
        }

        .date-input.month {
            min-width: 80px;
        }
        
        .date-input.year {
            min-width: 40px;
        }

        .receipt-card-right .date-input {
            font-size: 10px;
            min-width: 20px;
        }
        .receipt-card-right .date-input.month {
            min-width: 50px;
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 15px; 
            align-items: flex-end;
        }

        .signature-box {
            text-align: center;
            font-size: 11px;
            color: black;
            font-style: italic;
            font-weight: bold;
            width: 110px;
        }
        
        .receipt-card-right .signature-box {
            font-size: 9px;
            width: 70px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-bottom: 5px;
        }

        /* QR Code Container */
        .qr-container {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-container img {
            width: 50px;
            height: 50px;
        }

        .qr-label {
            font-size: 7px;
            color: #1A237E;
            font-weight: bold;
            margin-top: 2px;
        }
    </style>
</head>
<body onload="window.print()">

<div class="page-container">

    <!-- LEFT RECEIPT (LARGER) -->
    <div class="receipt-card receipt-card-left">
        
        <div class="header">
            <div class="logo-container">
                @if(isset($lotificacion) && $lotificacion->logo)
                    <img src="{{ asset('storage/'.$lotificacion->logo) }}" alt="Logo">
                @else
                    <div class="logo-placeholder">Sin Logo</div>
                @endif
            </div>
            <div class="company-info">
                <div>{{ strtoupper($lotificacion->nombre ?? 'LOTIFICACION') }}</div>
                <div>RUC {{ $lotificacion->ruc ?? '----------------' }}</div>
                <div>TELEFONO DE CONTACTO ({{ $lotificacion->telefono ?? '--------' }})</div>
                <div>{{ strtoupper($lotificacion->ciudad ?? 'CIUDAD') }}</div>
            </div>
            <div class="receipt-number-container">
                {{ $pago->id_abono }}
            </div>
        </div>

        <div class="title-block">
            <div class="title">RECIBO</div>
            <div class="amount-boxes-container">
                <div class="amount-box">
                    POR C$: <div class="amount-input"></div>
                </div>
                <div class="amount-box">
                    POR U$: <div class="amount-input">{{ number_format($pago->monto_abonado, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="label">Recibimos de:</div>
            <div class="value">{{ $cliente->nombres_apellidos ?? 'Cliente Desconocido' }}</div>
        </div>

        <div class="row">
            <div class="label">La suma de:</div>
            <div class="value">{{ $monto_en_letras }} NETOS</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value" style="display:flex; justify-content: space-between;">
                <span>{{ $pago->tipo_pago }}</span>
                <span>{{ $cliente->pv_num ?? '001' }}</span>
            </div>
        </div>

        @if(in_array($pago->metodo_pago, ['Transferencia Bancaria', 'Depósito Bancario', 'Cheque']))
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Vía:</span> {{ $pago->metodo_pago }} 
            @if($pago->cuenta_destino) &nbsp;|&nbsp; <span style="color:#1A237E;">Cta:</span> {{ $pago->cuenta_destino }} @endif
            @if($pago->referencia) &nbsp;|&nbsp; <span style="color:#1A237E;">Ref:</span> {{ $pago->referencia }} @endif
        </div>
        @elseif($pago->referencia && $pago->referencia !== 'Registro Inicial de Venta' && $pago->referencia !== 'Abono de Cuota')
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Ref/Comentarios:</span> {{ $pago->referencia }}
        </div>
        @endif

        <div class="details-row">
            Lotes: {{ $lotes_texto }} &nbsp;&nbsp; Total: ${{ number_format($valor_total, 2) }} | Abonado: ${{ number_format($total_abonado, 2) }} | Pendiente: ${{ number_format($saldo_pendiente, 2) }}
        </div>

        <div class="details-row">
            Cuota: ${{ number_format($venta->cuota_mensual ?? 0, 2) }}/mes | Plazo: {{ $venta->plazo_meses ?? 0 }} meses | Faltan: {{ $abonos_faltantes }}
        </div>

        <div class="date-row">
            A los 
            <div class="date-input">{{ date('d', strtotime($pago->fecha_pago)) }}</div> 
            dias del mes de 
            <div class="date-input month">{{ ucfirst(\Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->monthName) }}</div> 
            del 
            <div class="date-input year">{{ date('Y', strtotime($pago->fecha_pago)) }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                Recibí Conforme
            </div>

            <!-- QR CODE -->
            <div style="display:flex; flex-direction:column; align-items:center;">
                @if(isset($cliente) && $cliente->token_seguimiento)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data={{ urlencode(route('portal.estado_cuenta', $cliente->token_seguimiento)) }}" alt="QR Code">
                @else
                    <div style="width: 70px; height: 70px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #999; text-align: center;">Sin Token</div>
                @endif
                <span style="font-size: 8px; color: #1A237E; font-weight: bold; margin-top: 3px;">Estado de Cuenta</span>
            </div>

            <div class="signature-box">
                <div class="signature-line"></div>
                Entregué Conforme<br>
                <span style="font-size: 7px; font-weight: normal; color: #555;">Cajero: {{ $pago->user->name ?? 'Sistema' }}</span>
            </div>
        </div>
    </div>

    <!-- RIGHT RECEIPT (SMALLER) -->
    <div class="receipt-card receipt-card-right">
        
        <div class="header">
            <div class="logo-container">
                @if(isset($lotificacion) && $lotificacion->logo)
                    <img src="{{ asset('storage/'.$lotificacion->logo) }}" alt="Logo">
                @else
                    <div class="logo-placeholder">Sin Logo</div>
                @endif
            </div>
            <div class="company-info">
                <div>{{ strtoupper($lotificacion->nombre ?? 'LOTIFICACION') }}</div>
                <div>RUC {{ $lotificacion->ruc ?? '----------------' }}</div>
                <div>TELEFONO DE CONTACTO ({{ $lotificacion->telefono ?? '--------' }})</div>
                <div>{{ strtoupper($lotificacion->ciudad ?? 'CIUDAD') }}</div>
            </div>
            <div class="receipt-number-container">
                {{ $pago->id_abono }}
            </div>
        </div>

        <div class="title-block">
            <div class="title">RECIBO</div>
            <div class="amount-boxes-container">
                <div class="amount-box">
                    C$: <div class="amount-input"></div>
                </div>
                <div class="amount-box">
                    U$: <div class="amount-input">{{ number_format($pago->monto_abonado, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="label">Recibimos de:</div>
            <div class="value">{{ $cliente->nombres_apellidos ?? 'Cliente Desconocido' }}</div>
        </div>

        <div class="row">
            <div class="label">La suma de:</div>
            <div class="value">{{ $monto_en_letras }} NETOS</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value" style="display:flex; justify-content: space-between;">
                <span>{{ $pago->tipo_pago }}</span>
                <span>{{ $cliente->pv_num ?? '001' }}</span>
            </div>
        </div>

        @if(in_array($pago->metodo_pago, ['Transferencia Bancaria', 'Depósito Bancario', 'Cheque']))
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Vía:</span> {{ $pago->metodo_pago }} 
            @if($pago->cuenta_destino) &nbsp;|&nbsp; <span style="color:#1A237E;">Cta:</span> {{ $pago->cuenta_destino }} @endif
            @if($pago->referencia) &nbsp;|&nbsp; <span style="color:#1A237E;">Ref:</span> {{ $pago->referencia }} @endif
        </div>
        @elseif($pago->referencia && $pago->referencia !== 'Registro Inicial de Venta' && $pago->referencia !== 'Abono de Cuota')
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Ref/Comentarios:</span> {{ $pago->referencia }}
        </div>
        @endif

        <div class="details-row">
            Lotes: {{ $lotes_texto }}<br>Total: ${{ number_format($valor_total, 2) }} | Pendiente: ${{ number_format($saldo_pendiente, 2) }}
        </div>

        <div class="details-row">
            Cuota: ${{ number_format($venta->cuota_mensual ?? 0, 2) }}/mes<br>Plazo: {{ $venta->plazo_meses ?? 0 }} meses | Faltan: {{ $abonos_faltantes }}
        </div>

        <div class="date-row">
            A los 
            <div class="date-input">{{ date('d', strtotime($pago->fecha_pago)) }}</div> 
            de 
            <div class="date-input month">{{ ucfirst(\Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->monthName) }}</div> 
            del 
            <div class="date-input year">{{ date('Y', strtotime($pago->fecha_pago)) }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                Recibí Conforme
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                Entregué Conforme<br>
                <span style="font-size: 7px; font-weight: normal; color: #555;">Cajero: {{ $pago->user->name ?? 'Sistema' }}</span>
            </div>
        </div>
    </div>

</div>

</body>
</html>