<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Datos Legales para Promesas de Venta</title>
    <style>
        @page {
            size: letter landscape;
            margin: 8mm 10mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #1a202c;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5px solid #1A237E;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .logo-img {
            max-height: 45px;
            max-width: 120px;
        }
        .company-title {
            font-size: 13px;
            font-weight: bold;
            color: #1A237E;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            margin-top: 2px;
        }
        .meta-info {
            text-align: right;
            font-size: 7.5px;
            color: #64748b;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .data-table th {
            background-color: #1A237E;
            color: #ffffff;
            font-weight: bold;
            padding: 4px 5px;
            text-align: left;
            border: 0.5px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 7px;
        }
        .data-table td {
            padding: 3.5px 5px;
            border: 0.5px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-primary { color: #1A237E; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .footer {
            margin-top: 10px;
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
            border-top: 0.5px solid #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                @if(isset($logoBase64) && $logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                @else
                    <div class="company-title">{{ $nombreProyecto }}</div>
                @endif
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="company-title">{{ $nombreProyecto }}</div>
                <div class="report-title">Expediente Legal de Compradores (Promesas de Venta)</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">
                    @if($fechaInicio || $fechaFin)
                        Período: {{ $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : 'Inicio' }} al {{ $fechaFin ? date('d/m/Y', strtotime($fechaFin)) : 'Actualidad' }}
                    @else
                        Consolidado General de Contratos
                    @endif
                </div>
            </td>
            <td style="width: 25%;" class="meta-info">
                <strong>Fecha Impresión:</strong> {{ $generadoEl }}<br>
                <strong>Total Registros:</strong> {{ number_format($totalClientes) }} contratos<br>
                <strong>Usuario:</strong> {{ auth()->user()->name ?? 'Sistema' }}
            </td>
        </tr>
    </table>

    <table class="summary-box" style="width: 100%; font-size: 8px;">
        <tr>
            <td style="width: 25%;"><strong>Total Contratos:</strong> {{ number_format($totalClientes) }}</td>
            <td style="width: 25%;"><strong>Monto Contratado:</strong> ${{ number_format($totalPrecioVentas, 2) }}</td>
            <td style="width: 25%;"><strong>Primas Recaudadas:</strong> ${{ number_format($totalPrimas, 2) }}</td>
            <td style="width: 25%;"><strong>Área Total:</strong> {{ number_format($totalAreaM2, 2) }} m²</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">N° PV / Exp</th>
                <th style="width: 14%;">Comprador</th>
                <th style="width: 9%;">Cédula / Identif.</th>
                <th style="width: 7%;">Civil / Oficio</th>
                <th style="width: 8%;">Teléfono</th>
                <th style="width: 12%;">Dirección</th>
                <th style="width: 10%;">Lote / Proyecto</th>
                <th style="width: 6%;" class="text-right">Área (m²)</th>
                <th style="width: 7%;" class="text-right">Precio ($)</th>
                <th style="width: 7%;" class="text-right">Prima ($)</th>
                <th style="width: 6%;" class="text-center">Plazo</th>
                <th style="width: 8%;">Beneficiario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventasData as $v)
            <tr>
                <td><strong>{{ $v['pv_num'] }}</strong><br><span style="color:#64748b;">{{ $v['expediente_num'] }}</span></td>
                <td><strong class="text-primary">{{ $v['cliente_nombre'] }}</strong></td>
                <td style="font-family: monospace;">{{ $v['identificacion'] }}</td>
                <td>{{ $v['estado_civil'] }}<br><span style="color:#64748b;">{{ $v['oficio'] }}</span></td>
                <td>{{ $v['telefono'] }}</td>
                <td>{{ $v['direccion'] }}</td>
                <td><strong>{{ $v['lotes_texto'] }}</strong><br><span style="color:#64748b;">{{ $v['proyecto_nombre'] }}</span></td>
                <td class="text-right">{{ number_format($v['area_metros'], 2) }}</td>
                <td class="text-right fw-bold">${{ number_format($v['precio_final'], 2) }}</td>
                <td class="text-right text-success">${{ number_format($v['prima_pagada'], 2) }}</td>
                <td class="text-center">{{ $v['plazo_meses'] }}m<br><span style="color:#64748b;">${{ number_format($v['cuota_mensual'], 2) }}</span></td>
                <td>{{ $v['beneficiario_final'] ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 10px;">No se encontraron registros.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado para fines de control administrativo y elaboración de Promesas de Venta Notariales.
    </div>

</body>
</html>
