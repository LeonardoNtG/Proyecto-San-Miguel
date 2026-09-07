<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cierre Diario - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 15mm 20mm 15mm;
        }
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        }
        body {
            color: #1e293b;
            background-color: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1 0 auto;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-height: 65px;
            max-width: 160px;
            object-fit: contain;
        }
        .company-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-title {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 11px;
            color: #475569;
            margin-top: 3px;
            font-weight: 500;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 9px 14px;
            margin-bottom: 14px;
        }
        .meta-table {
            width: 100%;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 11px;
            vertical-align: top;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 14px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .kpi-cell {
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
        }
        .kpi-title {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 800;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 6px 7px;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
            font-weight: 700;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            font-size: 10px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }
        .fw-bold { font-weight: bold !important; }
        
        .badge-recibo-num {
            font-weight: 800;
            color: #0f172a;
        }
        .badge-blanco {
            color: #64748b;
            font-style: italic;
        }
        .letras-box {
            margin-bottom: 14px;
            font-size: 10.5px;
            background-color: #f8fafc;
            padding: 7px 12px;
            border-radius: 4px;
            border-left: 4px solid #0f172a;
        }
        .signatures-section {
            margin-top: auto;
            padding-top: 40px;
            page-break-inside: avoid;
        }
        .signatures-table {
            width: 100%;
        }
        .signature-box {
            text-align: center;
            width: 42%;
        }
        .signature-line {
            border-top: 1.5px solid #0f172a;
            margin: 0 25px 5px 25px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e2e8f0;
        }
        .btn-print {
            background-color: #0f172a;
            color: white;
            border: none;
            padding: 9px 22px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir Reporte
        </button>
        <button class="btn-print" style="background-color: #64748b; margin-left: 10px;" onclick="window.close()">
            Cerrar
        </button>
    </div>

    <div class="main-content">
        {{-- ENCABEZADO CON LOGO --}}
        <table class="header-table">
            <tr>
                <td style="width: 28%; vertical-align: middle;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                    @else
                        <div class="company-name">{{ $lotificacion ? $lotificacion->nombre : 'SISTEMA SAN MIGUEL' }}</div>
                    @endif
                </td>
                <td style="width: 48%; text-align: center; vertical-align: middle;">
                    <h1 class="header-title">Reporte de Cierre Diario</h1>
                    <div class="header-subtitle">
                        {{ $lotificacion ? $lotificacion->nombre : 'Consolidado General de Operaciones' }}
                    </div>
                </td>
                <td style="width: 24%; text-align: right; vertical-align: middle;">
                    <div style="font-size: 9.5px; color: #64748b;">Fecha de Emisión:</div>
                    <div style="font-size: 11px; font-weight: 700;">{{ now()->format('d/m/Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        {{-- METADATOS DEL CIERRE --}}
        <div class="meta-box">
            <table class="meta-table">
                <tr>
                    <td style="width: 30%;">
                        <strong style="color: #64748b; font-size: 9.5px; text-transform: uppercase;">Fecha del Corte:</strong><br>
                        <span style="font-weight: 700; font-size: 11.5px;">{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</span>
                    </td>
                    <td style="width: 35%;">
                        <strong style="color: #64748b; font-size: 9.5px; text-transform: uppercase;">Proyecto / Lotificación:</strong><br>
                        <span style="font-weight: 700; font-size: 11.5px;">{{ $lotificacion ? $lotificacion->nombre : 'Todos los Proyectos' }}</span>
                    </td>
                    <td style="width: 20%;">
                        <strong style="color: #64748b; font-size: 9.5px; text-transform: uppercase;">Elaborado Por:</strong><br>
                        <span style="font-weight: 700; font-size: 11.5px;">{{ auth()->user() ? auth()->user()->name : 'Cajero Responsable' }}</span>
                    </td>
                    <td style="width: 15%; text-align: right;">
                        <strong style="color: #64748b; font-size: 9.5px; text-transform: uppercase;">Total Recaudado:</strong><br>
                        <span style="font-size: 14px; font-weight: 800; color: #059669;">${{ number_format($totalMonto, 2) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- TARJETAS KPI RESUMEN --}}
        <div class="kpi-container">
            <table class="kpi-table">
                <tr>
                    <td class="kpi-cell" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; width: 50%;">
                        <div class="kpi-title" style="color: #065f46;">Total Recaudado</div>
                        <div class="kpi-value" style="color: #059669;">${{ number_format($totalMonto, 2) }}</div>
                    </td>
                    <td class="kpi-cell" style="background-color: #f0f9ff; border: 1px solid #bae6fd; width: 50%;">
                        <div class="kpi-title" style="color: #0369a1;">Total Recibos Emitidos</div>
                        <div class="kpi-value" style="color: #0284c7;">{{ $totalRecibos }}</div>
                    </td>
                </tr>
            </table>
        </div>

        @if($totalMonto > 0)
            <div class="letras-box">
                <strong>SON:</strong> {{ $montoEnLetras }} DÓLARES NETOS
            </div>
        @endif

        {{-- TABLA DETALLADA DE MOVIMIENTOS --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 80px;">N° Recibo</th>
                    <th class="text-center" style="width: 70px;">Hora</th>
                    <th>Proyecto</th>
                    <th>Recibimos de (Cliente)</th>
                    <th>Concepto / Detalle</th>
                    <th>Motivo / Observación</th>
                    <th>Cajero</th>
                    <th class="text-end" style="width: 95px;">Monto ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recibos as $recibo)
                    <tr>
                        <td class="text-center badge-recibo-num">
                            {{ $recibo->numero_recibo_formateado }}
                        </td>
                        <td class="text-center" style="color: #475569;">
                            {{ $recibo->created_at ? $recibo->created_at->format('h:i A') : '--:--' }}
                        </td>
                        <td>
                            {{ $recibo->lotificacion?->nombre ?? 'Sin Proyecto' }}
                        </td>
                        <td>
                            @if($recibo->cliente_nombre)
                                <strong>{{ $recibo->cliente_nombre }}</strong>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $recibo->concepto ?: '—' }}
                            @if(!is_null($recibo->total_abonado) || !is_null($recibo->saldo_pendiente))
                                <div style="font-size: 8.5px; color: #64748b;">
                                    @if(!is_null($recibo->valor_total)) Total: ${{ number_format($recibo->valor_total, 2) }} | @endif
                                    @if(!is_null($recibo->total_abonado)) Abono: ${{ number_format($recibo->total_abonado, 2) }} | @endif
                                    @if(!is_null($recibo->saldo_pendiente)) Saldo: ${{ number_format($recibo->saldo_pendiente, 2) }} @endif
                                </div>
                            @endif
                        </td>
                        <td style="color: #475569;">
                            {{ $recibo->motivo ?: '—' }}
                        </td>
                        <td>
                            {{ $recibo->user?->name ?? 'Sistema' }}
                        </td>
                        <td class="text-end fw-bold">
                            @if(!is_null($recibo->monto) && (float)$recibo->monto > 0)
                                ${{ number_format($recibo->monto, 2) }}
                            @else
                                $0.00
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">
                            No se registraron movimientos en la fecha seleccionada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($totalRecibos > 0)
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="7" class="text-end" style="padding: 7px; font-size: 11px;">
                            TOTAL GENERAL RECAUDADO:
                        </td>
                        <td class="text-end" style="padding: 7px; font-size: 12px; color: #059669;">
                            ${{ number_format($totalMonto, 2) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- SECCIÓN DE FIRMAS AL FINAL DEL DOCUMENTO --}}
    <div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <strong style="font-size: 11px;">Elaborado por (Caja)</strong><br>
                    <span style="font-size: 10px; color: #475569;">{{ auth()->user() ? auth()->user()->name : 'Cajero Responsable' }}</span>
                </td>
                <td style="width: 16%;"></td>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <strong style="font-size: 11px;">Revisado y Conforme (Auditoría)</strong><br>
                    <span style="font-size: 10px; color: #475569;">Firma y Sello</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
