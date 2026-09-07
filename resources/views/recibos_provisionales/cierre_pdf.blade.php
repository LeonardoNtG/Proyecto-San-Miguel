<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Recibos Provisionales - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            color: #1e293b;
            background-color: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            margin: 0;
        }
        .header-subtitle {
            font-size: 11px;
            color: #475569;
            margin-top: 3px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .meta-table {
            width: 100%;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 11px;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 14px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-cell {
            padding: 8px 10px;
            border-radius: 6px;
            text-align: center;
        }
        .kpi-title {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 10px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }
        .fw-bold { font-weight: bold !important; }
        .badge-recibo {
            font-weight: bold;
            color: #b45309;
        }
        .badge-blanco {
            color: #64748b;
            font-style: italic;
        }
        .signatures-section {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signatures-table {
            width: 100%;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-line {
            border-top: 1px solid #1e293b;
            margin: 0 15px 4px 15px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e2e8f0;
        }
        .btn-print {
            background-color: #1e293b;
            color: white;
            border: none;
            padding: 8px 18px;
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
            🖨️ Imprimir / Guardar en PDF
        </button>
        <button class="btn-print" style="background-color: #64748b; margin-left: 10px;" onclick="window.close()">
            Cerrar
        </button>
    </div>

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <h1 class="header-title">Reporte de Cierre de Recibos Provisionales</h1>
                <div class="header-subtitle">
                    Sistema de Control &bull; Módulo de Recibos Manuales / Provisionales
                </div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="font-size: 10px; color: #64748b;">Fecha de Emisión:</div>
                <div style="font-size: 11px; font-weight: bold;">{{ now()->format('d/m/Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    {{-- METADATOS DEL CIERRE --}}
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 25%;">
                    <strong>Fecha del Corte:</strong><br>
                    {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </td>
                <td style="width: 25%;">
                    <strong>Proyecto / Lotificación:</strong><br>
                    {{ $lotificacion ? $lotificacion->nombre : 'Todos los Proyectos' }}
                </td>
                <td style="width: 25%;">
                    <strong>Generado por:</strong><br>
                    {{ auth()->user() ? auth()->user()->name : 'Usuario del Sistema' }}
                </td>
                <td style="width: 25%; text-align: right;">
                    <strong>Total Recaudado:</strong><br>
                    <span style="font-size: 13px; font-weight: bold; color: #059669;">${{ number_format($totalMonto, 2) }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- TARJETAS KPI --}}
    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td class="kpi-cell" style="background-color: #fef3c7; border: 1px solid #fde68a; width: 25%;">
                    <div class="kpi-title" style="color: #92400e;">Total Recaudado</div>
                    <div class="kpi-value" style="color: #b45309;">${{ number_format($totalMonto, 2) }}</div>
                </td>
                <td class="kpi-cell" style="background-color: #e0f2fe; border: 1px solid #bae6fd; width: 25%;">
                    <div class="kpi-title" style="color: #0369a1;">Recibos Emitidos</div>
                    <div class="kpi-value" style="color: #0284c7;">{{ $totalRecibos }}</div>
                </td>
                <td class="kpi-cell" style="background-color: #d1fae5; border: 1px solid #a7f3d0; width: 25%;">
                    <div class="kpi-title" style="color: #065f46;">Recibos con Monto</div>
                    <div class="kpi-value" style="color: #059669;">{{ $recibosConMonto }}</div>
                </td>
                <td class="kpi-cell" style="background-color: #f1f5f9; border: 1px solid #cbd5e1; width: 25%;">
                    <div class="kpi-title" style="color: #475569;">Recibos en Blanco</div>
                    <div class="kpi-value" style="color: #334155;">{{ $recibosEnBlanco }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($totalMonto > 0)
        <div style="margin-bottom: 12px; font-size: 10px; background-color: #f8fafc; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #f59e0b;">
            <strong>SON:</strong> {{ $montoEnLetras }} DÓLARES NETOS
        </div>
    @endif

    {{-- TABLA DETALLADA --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 80px;">N° Recibo</th>
                <th style="width: 60px;">Hora</th>
                <th>Proyecto</th>
                <th>Cliente / Recibimos de</th>
                <th>Concepto / Detalle</th>
                <th>Motivo / Observación</th>
                <th>Cajero</th>
                <th class="text-end" style="width: 85px;">Monto ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recibos as $recibo)
                <tr>
                    <td class="text-center badge-recibo">
                        {{ $recibo->numero_recibo_formateado }}
                    </td>
                    <td class="text-center">
                        {{ $recibo->created_at ? $recibo->created_at->format('h:i A') : '--:--' }}
                    </td>
                    <td>
                        {{ $recibo->lotificacion?->nombre ?? 'Sin Proyecto' }}
                    </td>
                    <td>
                        @if($recibo->cliente_nombre)
                            <strong>{{ $recibo->cliente_nombre }}</strong>
                        @else
                            <span class="badge-blanco">En Blanco</span>
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
                    <td style="color: #64748b;">
                        {{ $recibo->motivo ?: '—' }}
                    </td>
                    <td>
                        {{ $recibo->user?->name ?? 'Sistema' }}
                    </td>
                    <td class="text-end fw-bold">
                        @if(!is_null($recibo->monto) && (float)$recibo->monto > 0)
                            ${{ number_format($recibo->monto, 2) }}
                        @else
                            <span class="badge-blanco">En Blanco</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">
                        No se registraron recibos provisionales en la fecha seleccionada.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($totalRecibos > 0)
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="7" class="text-end" style="padding: 6px; font-size: 11px;">
                        TOTAL GENERAL:
                    </td>
                    <td class="text-end" style="padding: 6px; font-size: 12px; color: #059669;">
                        ${{ number_format($totalMonto, 2) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- SECCIÓN DE FIRMAS --}}
    <div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <strong>Elaborado por (Caja)</strong><br>
                    <span style="font-size: 9.5px; color: #64748b;">{{ auth()->user() ? auth()->user()->name : 'Cajero Responsable' }}</span>
                </td>
                <td style="width: 5%;"></td>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <strong>Revisado por (Auditoría)</strong><br>
                    <span style="font-size: 9.5px; color: #64748b;">Firma y Sello</span>
                </td>
                <td style="width: 5%;"></td>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <strong>Autorizado por (Gerencia)</strong><br>
                    <span style="font-size: 9.5px; color: #64748b;">Firma y Sello</span>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Auto abrir cuadro de impresión si el usuario lo solicita
            // window.print();
        });
    </script>
</body>
</html>
