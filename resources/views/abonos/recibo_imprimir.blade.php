<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo N° {{ $numeroReciboMostrar }}</title>
    <style>
        /* CSS Reset for Printing */
        @page {
            size: letter portrait;
            margin: 5mm 6mm;
        }
        body, html {
            margin: 0;
            padding: 5mm 6mm;
            width: 100%;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            background-color: #f8fafc;
        }
        * {
            box-sizing: inherit;
        }
        
        .page-container {
            width: 100%;
            max-width: 8.2in;
            margin: 0 auto;
            min-height: 3.8in;
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 8px;
            padding: 0;
            background-color: white;
        }

        .receipt-card {
            border: 2px solid #1A237E; /* Deep Blue border */
            position: relative;
            padding: 15px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: white;
        }

        @if($imprimirDoble)
            /* FORMATO DOBLE VÍA (CONFIGURABLE POR PROYECTO) */
            .receipt-card-left {
                width: {{ $anchoCliente ?? 'calc(50% - 4px)' }};
            }
            .receipt-card-right {
                width: {{ $anchoEmpresa ?? 'calc(50% - 4px)' }};
            }
        @else
            /* FORMATO RECIBO ÚNICO (100% DEL ESPACIO) */
            .page-container {
                display: block;
            }
            .receipt-card-left {
                width: 100% !important;
                min-height: 4.2in;
                padding: 22px 24px;
            }
            .receipt-card-right {
                display: none !important;
            }
            .title {
                font-size: 28px !important;
            }
            .company-info {
                font-size: 13px !important;
            }
            .row {
                font-size: 13px !important;
                margin-bottom: 10px !important;
            }
            .row .value {
                font-size: 14px !important;
            }
            .details-row {
                font-size: 11px !important;
                margin-bottom: 7px !important;
            }
            .date-row {
                font-size: 13px !important;
                margin-top: 10px !important;
                margin-bottom: 10px !important;
            }
            .date-input {
                font-size: 14px !important;
            }
            .signature-box {
                width: 170px !important;
                font-size: 13px !important;
            }
            .signatures {
                margin-top: 65px !important;
            }
        @endif

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

        .receipt-card-right .company-info {
            font-size: 10px;
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

        .receipt-card-right .title {
            font-size: 20px;
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
        
        .receipt-card-right .amount-box {
            font-size: 10px;
        }

        .amount-input {
            border: 2px solid #444;
            background-color: #e0e0e0;
            width: 60px;
            height: 19px;
            display: inline-block;
            margin-left: 4px;
            text-align: center;
            line-height: 17px;
            color: black;
            box-shadow: 1.5px 1.5px 0px #444;
            font-weight: bold;
            font-size: 10.5px;
        }

        .receipt-card-right .amount-input {
            width: 60px;
            font-size: 10.5px;
            box-shadow: 1.5px 1.5px 0px #444;
        }

        /* Body Rows */
        .row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 7px;
            font-size: 10.5px;
            color: #1A237E;
            font-weight: bold;
        }

        .receipt-card-right .row {
            font-size: 10.5px;
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
            font-size: 11px;
        }
        
        .receipt-card-right .row .value {
            font-size: 11px;
        }

        /* Small details row */
        .details-row {
            font-size: 8.5px;
            color: black;
            font-weight: bold;
            margin-bottom: 5px;
            margin-left: 2px;
        }

        .receipt-card-right .details-row {
            font-size: 8.5px;
        }
        
        /* Date Row */
        .date-row {
            display: flex;
            align-items: baseline;
            margin-top: 5px;
            margin-bottom: 5px;
            font-size: 10.5px;
            color: #1A237E;
            font-weight: bold;
        }

        .receipt-card-right .date-row {
            font-size: 10.5px;
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
            min-width: 65px;
        }
        
        .date-input.year {
            min-width: 35px;
        }

        .receipt-card-right .date-input {
            font-size: 11px;
            min-width: 25px;
        }
        .receipt-card-right .date-input.month {
            min-width: 65px;
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 65px; 
            align-items: flex-start;
        }

        .receipt-card-right .signatures {
            margin-top: 65px;
        }

        .signature-box {
            text-align: center;
            font-size: 10px;
            color: black;
            font-style: italic;
            font-weight: bold;
            width: 105px;
        }
        
        .receipt-card-right .signature-box {
            font-size: 10px;
            width: 105px;
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
            body, html {
                background: none;
                padding: 0;
                margin: 0;
            }
            .page-container {
                padding: 0;
                margin: 0 auto;
                width: 100%;
                max-width: 100%;
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
            <div class="value">{{ preg_replace('/\b(DÓLARES|DOLARES)\s+(DÓLARES|DOLARES)\b/ui', 'DÓLARES', trim($monto_en_letras . ' ' . $sufijoMoneda)) }}</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value" style="display:flex; justify-content: space-between;">
                <span>Abono a {{ $venta->lotes->count() > 1 ? 'Lotes' : 'Lote' }} {{ $lotes_texto }}</span>
            </div>
        </div>

        @if(in_array($pago->metodo_pago, ['Transferencia Bancaria', 'Depósito Bancario', 'Cheque']))
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Vía:</span> {{ $pago->metodo_pago }} 
            @if($pago->cuenta_destino) &nbsp;|&nbsp; <span style="color:#1A237E;">Cta:</span> {{ $pago->cuenta_destino }} @endif
            @if($pago->referencia) &nbsp;|&nbsp; <span style="color:#1A237E;">Ref:</span> {{ $pago->referencia }} @endif
        </div>
        @endif

        <div class="details-row">
            Total: ${{ number_format($valor_total, 2) }} | Abonado: ${{ number_format($total_abonado, 2) }} | Saldo Actual: ${{ number_format($saldo_pendiente, 2) }}
        </div>

        <div class="details-row">
            Cuota: ${{ number_format($venta->cuota_mensual ?? 0, 2) }}/mes | Plazo: {{ $venta->plazo_meses ?? 0 }} Meses | Cuotas Pendientes: {{ $abonos_faltantes }}
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

            @if($mostrarQr)
            <!-- QR CODE -->
            <div style="display:flex; flex-direction:column; align-items:center;">
                @if(!empty($cliente->token_seguimiento))
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data={{ urlencode(route('portal.estado_cuenta', $cliente->token_seguimiento)) }}" alt="QR Code">
                @else
                    <div style="width: 50px; height: 50px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #999; text-align: center;">Sin Token</div>
                @endif
                <span style="font-size: 8px; color: #1A237E; font-weight: bold; margin-top: 3px;">Estado de Cuenta</span>
            </div>
            @endif

            <div class="signature-box">
                <div class="signature-line"></div>
                Entregué Conforme<br>
                <span style="font-size: 7px; font-weight: normal; color: #555;">Cajero: {{ $pago->user->name ?? 'Sistema' }}</span>
            </div>
        </div>

        @if(!empty($leyendaPie))
            <div class="legal-notice">{{ $leyendaPie }}</div>
        @endif
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
            <div class="value">{{ preg_replace('/\b(DÓLARES|DOLARES)\s+(DÓLARES|DOLARES)\b/ui', 'DÓLARES', trim($monto_en_letras . ' ' . $sufijoMoneda)) }}</div>
        </div>

        <div class="row">
            <div class="label">En concepto de:</div>
            <div class="value" style="display:flex; justify-content: space-between;">
                <span>Abono a {{ $venta->lotes->count() > 1 ? 'Lotes' : 'Lote' }} {{ $lotes_texto }}</span>
            </div>
        </div>

        @if(in_array($pago->metodo_pago, ['Transferencia Bancaria', 'Depósito Bancario', 'Cheque']))
        <div class="details-row" style="margin-top: 4px; margin-bottom: 8px;">
            <span style="color:#1A237E;">Vía:</span> {{ $pago->metodo_pago }} 
            @if($pago->cuenta_destino) &nbsp;|&nbsp; <span style="color:#1A237E;">Cta:</span> {{ $pago->cuenta_destino }} @endif
            @if($pago->referencia) &nbsp;|&nbsp; <span style="color:#1A237E;">Ref:</span> {{ $pago->referencia }} @endif
        </div>
        @endif

        <div class="details-row">
            Total: ${{ number_format($valor_total, 2) }} | Saldo Actual: ${{ number_format($saldo_pendiente, 2) }}
        </div>

        <div class="details-row">
            Cuota: ${{ number_format($venta->cuota_mensual ?? 0, 2) }}/mes | Plazo: {{ $venta->plazo_meses ?? 0 }} Meses | Cuotas Pendientes: {{ $abonos_faltantes }}
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

        @if(!empty($leyendaPie))
            <div class="legal-notice">{{ $leyendaPie }}</div>
        @endif
    </div>
    @endif

</div>

</body>
</html>