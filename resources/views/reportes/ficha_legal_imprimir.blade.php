<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Legal PV - {{ $cliente->nombres_apellidos ?? 'Cliente' }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            color: #1a202c;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11.5px;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1A237E;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .logo-box {
            width: 100px;
            height: 70px;
            display: flex;
            align-items: center;
        }
        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .company-data {
            text-align: right;
            color: #1A237E;
            font-size: 10.5px;
            line-height: 1.3;
        }
        .doc-title-box {
            text-align: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 900;
            color: #1A237E;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .section-header {
            background: #1A237E;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            padding: 5px 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-body {
            padding: 10px 12px;
        }
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
        }
        .data-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px 16px;
        }
        .data-item {
            margin-bottom: 2px;
        }
        .data-label {
            font-weight: bold;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
        }
        .data-value {
            font-size: 12px;
            color: #0f172a;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 2px;
        }
        .signatures-container {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 220px;
            text-align: center;
        }
        .sig-line {
            border-top: 1.5px solid #000;
            margin-bottom: 5px;
        }
        .sig-name {
            font-weight: bold;
            font-size: 11px;
        }
        .sig-title {
            font-size: 9.5px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 25px;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print();">

    {{-- BARRA DE ACCIÓN EN PANTALLA --}}
    <div class="no-print" style="background: #2d3748; padding: 10px 20px; text-align: right; margin-bottom: 20px;">
        <button onclick="window.print();" style="background: #4e73df; color: white; border: none; padding: 6px 14px; border-radius: 4px; font-weight: bold; cursor: pointer;">
            🖨️ Imprimir Ficha
        </button>
    </div>

    {{-- MEMBRETE --}}
    <div class="header">
        <div class="logo-box">
            @if($lotificacion && $lotificacion->logo)
                <img src="{{ asset('storage/' . $lotificacion->logo) }}" alt="Logo">
            @else
                <div style="font-weight:bold; color:#1A237E; font-size:16px;">{{ $lotificacion->nombre ?? 'INMOBILIARIA' }}</div>
            @endif
        </div>
        <div class="company-data">
            <strong style="font-size: 13px;">{{ strtoupper($lotificacion->nombre ?? 'LOTIFICACIÓN SAN MIGUEL') }}</strong><br>
            RUC: {{ $lotificacion->ruc ?? '----------------' }}<br>
            Teléfono: {{ $lotificacion->telefono ?? '--------' }} &middot; {{ $lotificacion->ciudad ?? 'Nicaragua' }}<br>
            <span style="color: #64748b;">Fecha Emisión: {{ now()->format('d/m/Y h:i A') }}</span>
        </div>
    </div>

    {{-- TÍTULO --}}
    <div class="doc-title-box">
        <h1 class="doc-title">Ficha Técnica y Generales de Ley para Promesa de Venta</h1>
        <div class="doc-subtitle">
            Expediente: <strong>{{ $cliente->expediente_num ?? 'EXP-'.$cliente->id_cliente }}</strong> &nbsp;|&nbsp; 
            N° PV: <strong>{{ $cliente->pv_num ?? 'PV-'.$venta->id_venta }}</strong> &nbsp;|&nbsp; 
            Contrato: <strong>{{ $venta->estado_contrato }}</strong>
        </div>
    </div>

    {{-- SECCIÓN 1: DATOS PERSONALES DEL COMPRADOR --}}
    <div class="section-box">
        <div class="section-header">1. Generales de Ley del Comprador (Promitente Comprador)</div>
        <div class="section-body">
            <div class="data-grid" style="margin-bottom: 8px;">
                <div class="data-item">
                    <div class="data-label">Nombres y Apellidos Completos:</div>
                    <div class="data-value" style="font-weight: bold; font-size: 13px;">{{ $cliente->nombres_apellidos }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Cédula / Cédula de Identidad / Pasaporte:</div>
                    <div class="data-value" style="font-weight: bold; font-family: monospace; font-size: 13px;">{{ $cliente->identificacion ?: 'NO REGISTRADA' }}</div>
                </div>
            </div>
            <div class="data-grid-3">
                <div class="data-item">
                    <div class="data-label">Estado Civil:</div>
                    <div class="data-value">{{ $cliente->estado_civil ?: 'No Especificado' }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Profesión / Ocupación / Oficio:</div>
                    <div class="data-value">{{ $cliente->oficio ?: 'No Especificado' }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Teléfono de Contacto:</div>
                    <div class="data-value">{{ $cliente->telefono ?: 'N/A' }}</div>
                </div>
            </div>
            <div class="data-item" style="margin-top: 8px;">
                <div class="data-label">Dirección Domiciliar Exacta:</div>
                <div class="data-value">{{ $cliente->direccion ?: 'No Registrada en Sistema' }}</div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: IDENTIFICACIÓN DEL INMUEBLE --}}
    <div class="section-box">
        <div class="section-header">2. Descripción e Identificación del Inmueble</div>
        <div class="section-body">
            <div class="data-grid">
                <div class="data-item">
                    <div class="data-label">Proyecto / Lotificación:</div>
                    <div class="data-value" style="font-weight: bold;">{{ $lotificacion->nombre ?? 'Proyecto Principal' }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Lote(s) Adquirido(s):</div>
                    <div class="data-value" style="font-weight: bold; color: #1A237E;">
                        {{ $venta->lotes->map(fn($l) => ($l->bloque ? 'Bloque '.$l->bloque->nombre.' - ' : '').'Lote '.$l->numero_lote)->implode(', ') ?: 'Lote '.$venta->lotes_texto }}
                    </div>
                </div>
            </div>
            <div class="data-grid-3" style="margin-top: 8px;">
                <div class="data-item">
                    <div class="data-label">Área Total en Metros Cuadrados:</div>
                    <div class="data-value" style="font-weight: bold;">{{ number_format($areaTotalM2, 2) }} m²</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Área Equivalente en Varas:</div>
                    <div class="data-value" style="font-weight: bold;">{{ number_format($areaTotalV2, 2) }} v²</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Ubicación del Proyecto:</div>
                    <div class="data-value">{{ $lotificacion->ubicacion ?? ($lotificacion->ciudad ?? 'Nicaragua') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 3: CONDICIONES ECONÓMICAS Y FINANCIERAS --}}
    <div class="section-box">
        <div class="section-header">3. Términos Económicos y Plan Financiero</div>
        <div class="section-body">
            <div class="data-grid-3">
                <div class="data-item">
                    <div class="data-label">Precio Total de Venta:</div>
                    <div class="data-value" style="font-weight: bold; font-size: 13px; color: #1A237E;">
                        ${{ number_format($venta->precio_final, 2) }} USD
                    </div>
                </div>
                <div class="data-item">
                    <div class="data-label">Prima / Primer Abono Pagado:</div>
                    <div class="data-value" style="font-weight: bold; color: #16a34a;">
                        ${{ number_format($primaMonto, 2) }} USD
                    </div>
                </div>
                <div class="data-item">
                    <div class="data-label">Saldo a Financiar:</div>
                    <div class="data-value" style="font-weight: bold; color: #dc2626;">
                        ${{ number_format($saldoFinanciar, 2) }} USD
                    </div>
                </div>
            </div>
            <div class="data-grid-3" style="margin-top: 8px;">
                <div class="data-item">
                    <div class="data-label">Plazo Acordado:</div>
                    <div class="data-value">{{ $venta->plazo_meses }} Meses ({{ round($venta->plazo_meses / 12, 1) }} años)</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Cuota Mensual Pactada:</div>
                    <div class="data-value" style="font-weight: bold; color: #1A237E;">
                        ${{ number_format($venta->cuota_mensual, 2) }} USD / mes
                    </div>
                </div>
                <div class="data-item">
                    <div class="data-label">Fecha de Contrato / Venta:</div>
                    <div class="data-value">{{ $venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') : '-' }}</div>
                </div>
            </div>
            <div class="data-grid" style="margin-top: 8px; font-size: 10.5px; background: #f8fafc; padding: 6px 8px; border-radius: 4px;">
                <div>Total Abonado a la Fecha: <strong>${{ number_format($totalAbonado, 2) }}</strong></div>
                <div>Saldo Pendiente Actual: <strong>${{ number_format($saldoPendiente, 2) }}</strong></div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 4: BENEFICIARIO FINAL / CESIÓN --}}
    @if($venta->beneficiario_final)
    <div class="section-box">
        <div class="section-header">4. Designación de Beneficiario Final / Futuro Titular</div>
        <div class="section-body">
            <div class="data-grid">
                <div class="data-item">
                    <div class="data-label">Nombres del Beneficiario:</div>
                    <div class="data-value" style="font-weight: bold;">{{ $venta->beneficiario_final }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Observaciones / Cédula / Vínculo:</div>
                    <div class="data-value">{{ $venta->nota_beneficiario ?: 'Designado como titular final al cancelar el lote.' }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ÁREA DE FIRMAS --}}
    <div class="signatures-container">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $cliente->nombres_apellidos }}</div>
            <div class="sig-title">Promitente Comprador<br>Cédula: {{ $cliente->identificacion ?: '----------------' }}</div>
        </div>

        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">{{ setting('nombre_administrador_aprobador', 'Administración', $lotificacion?->id) }}</div>
            <div class="sig-title">Por la Inmobiliaria / Vendedor<br>{{ strtoupper($lotificacion->nombre ?? 'LOTIFICADORA') }}</div>
        </div>
    </div>

    <div class="footer-note">
        Este documento certifica los datos generales y términos pactados en el sistema para la redacción de la Promesa de Venta Notarial.
    </div>

</body>
</html>
