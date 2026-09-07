<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo Provisional N° {{ $numeroReciboMostrar }}</title>
    <style>
        /* CSS Reset for Printing */
        @page {
            size: landscape;
            margin: 0;
        }
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
        }
        * {
            box-sizing: border-box;
        }
        
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 0 12mm;
            box-sizing: border-box;
        }

        .page-container {
            width: 100%;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: stretch;
            gap: 8px;
            background-color: transparent;
            margin: auto 0;
        }

        .receipt-card {
            border: 2px solid #1A237E; /* Deep Blue border */
            position: relative;
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: white;
            flex: 1 1 0;
            min-width: 0;
            width: 0; /* fuerza flex a repartir igualmente */
            min-height: 380px;
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
            font-size: 10px;
            font-weight: bold;
            line-height: 1.2;
        }

        .receipt-number-container {
            min-width: 50px;
            text-align: right;
            color: #D32F2F; /* Red */
            font-weight: bold;
            font-size: 15px;
        }

        /* Title block */
        .title-block {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-right: 5px;
        }

        .title {
            color: #1A237E;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .amount-boxes-container {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .amount-box {
            display: flex;
            align-items: center;
            font-size: 10px;
            font-weight: bold;
            color: #1A237E;
        }

        .amount-input {
            border: 2px solid #444;
            background-color: #e0e0e0;
            width: 65px;
            height: 20px;
            display: inline-block;
            margin-left: 4px;
            text-align: center;
            line-height: 18px;
            color: black;
            box-shadow: 1.5px 1.5px 0px #444;
            font-weight: bold;
            font-size: 11px;
        }

        /* Body Rows */
        .row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 9px;
            font-size: 11px;
            color: #1A237E;
            font-weight: bold;
        }

        .row .label {
            white-space: nowrap;
            margin-right: 6px;
        }

        .row .value {
            flex-grow: 1;
            border-bottom: 1px solid #333;
            color: black;
            padding-left: 4px;
            font-size: 11.5px;
            min-height: 16px;
        }
        
        /* Date Row */
        .date-row {
            display: flex;
            align-items: baseline;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 11px;
            color: #1A237E;
            font-weight: bold;
        }

        .date-input {
            border-bottom: 1px solid #333;
            color: black;
            display: inline-block;
            text-align: center;
            min-width: 25px;
            margin: 0 4px;
            font-size: 11px;
        }

        .date-input.month {
            min-width: 70px;
        }
        
        .date-input.year {
            min-width: 40px;
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 55px; 
            align-items: flex-start;
        }

        .signature-box {
            text-align: center;
            font-size: 10px;
            color: black;
            font-style: italic;
            font-weight: bold;
            width: 120px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-bottom: 5px;
            width: 100%;
        }

        .legal-notice {
            font-size: 7.5px;
            color: #555;
            text-align: center;
            margin-top: 8px;
            font-style: italic;
        }

        /* Print Media Styles */
        @media print {
            html, body {
                background: none !important;
                width: 100% !important;
                height: 100% !important;
                min-height: 100vh !important;
                margin: 0 !important;
                padding: 0 10mm !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                box-sizing: border-box !important;
            }
            .page-container {
                width: 100% !important;
                padding: 0 !important;
                gap: 8px !important;
                justify-content: center !important;
                margin: auto 0 !important;
            }
            .receipt-card-left,
            .receipt-card-right {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                width: 0 !important;
            }
            .receipt-card {
                border: 2px solid #1A237E !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .amount-input {
                background-color: #e0e0e0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print();">

<div class="page-container">
    
    <!-- RECIBO DEL CLIENTE (PRINCIPAL) -->
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
                {{ $numeroReciboMostrar }}
            </div>
        </div>

        <div class="title-block">
            <div class="title">
                RECIBO
            </div>
            <div class="amount-boxes-container">
                <div class="amount-box">
                    POR C$: <div class="amount-input"></div>
                </div>
                <div class="amount-box">
                    POR U$: <div class="amount-input">{{ $recibo->monto ? number_format($recibo->monto, 2) : '' }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="label">Recibimos de:</div>
            <div class="value">{{ $recibo->cliente_nombre ?? '' }}</div>
        </div>

        <div class="row">
            <div class="label">La suma de:</div>
            <div class="value">{{ $recibo->monto_letras ? preg_replace('/\b(DÓLARES|DOLARES)\s+(DÓLARES|DOLARES)\b/ui', 'DÓLARES', trim($recibo->monto_letras . ' ' . $sufijoMoneda)) : '' }}</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value">{{ $recibo->concepto ?? '' }}</div>
        </div>

        <div class="row" style="margin-bottom: 6px;">
            <div class="value" style="font-weight: bold; font-size: 10.5px; padding-left: 2px;">
                @if($recibo->valor_total !== null || $recibo->total_abonado !== null)
                    Monto: U$ {{ number_format($recibo->valor_total ?? 0, 2) }}. Abonado: U$ {{ number_format($recibo->total_abonado ?? 0, 2) }}. Saldo: U$ {{ number_format($recibo->saldo_pendiente ?? max(0, ($recibo->valor_total ?? 0) - ($recibo->total_abonado ?? 0)), 2) }}
                @else
                    &nbsp;
                @endif
            </div>
        </div>

        <div class="date-row">
            A los 
            <div class="date-input">{{ $recibo->fecha ? date('d', strtotime($recibo->fecha)) : date('d') }}</div> 
            dias del mes de 
            <div class="date-input month">{{ ucfirst(\Carbon\Carbon::parse($recibo->fecha ?? now())->locale('es')->monthName) }}</div> 
            del 
            <div class="date-input year">{{ $recibo->fecha ? date('Y', strtotime($recibo->fecha)) : date('Y') }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                Recibí Conforme
            </div>

            <div class="signature-box">
                <div class="signature-line"></div>
                Entregué Conforme<br>
                <span style="font-size: 7px; font-weight: normal; color: #555;">Cajero: {{ $recibo->user->name ?? 'Sistema' }}</span>
            </div>
        </div>

        <div class="legal-notice">{{ $leyendaPie }}</div>
    </div>

    @if($imprimirDoble)
    <!-- RECIBO DE LA EMPRESA (TALÓN DE CONTROL) -->
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
                {{ $numeroReciboMostrar }}
            </div>
        </div>

        <div class="title-block">
            <div class="title">
                RECIBO
            </div>
            <div class="amount-boxes-container">
                <div class="amount-box">
                    C$: <div class="amount-input"></div>
                </div>
                <div class="amount-box">
                    U$: <div class="amount-input">{{ $recibo->monto ? number_format($recibo->monto, 2) : '' }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="label">Recibimos de:</div>
            <div class="value">{{ $recibo->cliente_nombre ?? '' }}</div>
        </div>

        <div class="row">
            <div class="label">La suma de:</div>
            <div class="value">{{ $recibo->monto_letras ? preg_replace('/\b(DÓLARES|DOLARES)\s+(DÓLARES|DOLARES)\b/ui', 'DÓLARES', trim($recibo->monto_letras . ' ' . $sufijoMoneda)) : '' }}</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value">{{ $recibo->concepto ?? '' }}</div>
        </div>

        <div class="row" style="margin-bottom: 6px;">
            <div class="value" style="font-weight: bold; font-size: 10.5px; padding-left: 2px;">
                @if($recibo->valor_total !== null || $recibo->total_abonado !== null)
                    Monto: U$ {{ number_format($recibo->valor_total ?? 0, 2) }}. Abonado: U$ {{ number_format($recibo->total_abonado ?? 0, 2) }}. Saldo: U$ {{ number_format($recibo->saldo_pendiente ?? max(0, ($recibo->valor_total ?? 0) - ($recibo->total_abonado ?? 0)), 2) }}
                @else
                    &nbsp;
                @endif
            </div>
        </div>

        <div class="date-row">
            A los 
            <div class="date-input">{{ $recibo->fecha ? date('d', strtotime($recibo->fecha)) : date('d') }}</div> 
            de 
            <div class="date-input month">{{ ucfirst(\Carbon\Carbon::parse($recibo->fecha ?? now())->locale('es')->monthName) }}</div> 
            del 
            <div class="date-input year">{{ $recibo->fecha ? date('Y', strtotime($recibo->fecha)) : date('Y') }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                Recibí Conforme
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                Entregué Conforme<br>
                <span style="font-size: 7px; font-weight: normal; color: #555;">Cajero: {{ $recibo->user->name ?? 'Sistema' }}</span>
            </div>
        </div>

        <div class="legal-notice">{{ $leyendaPie }}</div>
    </div>
    @endif

</div>

</body>
</html>
