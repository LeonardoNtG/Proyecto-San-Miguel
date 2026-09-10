<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cartera de Clientes</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #1e293b; line-height: 1.3; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #0f172a; padding-bottom: 8px; }
        .logo { max-height: 45px; max-width: 150px; object-fit: contain; }
        .title { font-size: 15px; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #64748b; margin: 2px 0 0; }
        
        .kpi-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .kpi-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 8px; text-align: center; }
        .kpi-title { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .kpi-val { font-size: 11px; font-weight: bold; color: #0f172a; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data-table th { background: #0f172a; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 5px 3px; text-align: left; }
        table.data-table td { font-size: 8.5px; padding: 4px 3px; border-bottom: 1px solid #e2e8f0; }
        table.data-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .footer { margin-top: 15px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

    <table style="width: 100%;" class="header">
        <tr>
            <td style="width: 70%; vertical-align: middle;">
                <h1 class="title">Cartera de Clientes, Contratos y Abonos</h1>
                <p class="subtitle">{{ $nombreProyecto }} &middot; Generado el {{ $generadoEl }}</p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                @endif
            </td>
        </tr>
    </table>

    <table class="kpi-table">
        <tr>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-box">
                    <div class="kpi-title">Total Contratos</div>
                    <div class="kpi-val">{{ number_format($totalContratos) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-box">
                    <div class="kpi-title">Valor Contratos</div>
                    <div class="kpi-val">${{ number_format($totalPrecioVentas, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-box">
                    <div class="kpi-title">Total Recaudado</div>
                    <div class="kpi-val" style="color: #059669;">${{ number_format($totalAbonadoGeneral, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Saldo por Cobrar</div>
                    <div class="kpi-val" style="color: #dc2626;">${{ number_format($totalSaldoGeneral, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Exp.</th>
                <th>Cliente</th>
                <th>Cédula</th>
                <th>Lote</th>
                <th class="text-end">Precio ($)</th>
                <th class="text-end">Abonado ($)</th>
                <th class="text-end">Saldo ($)</th>
                <th class="text-center">Cuotas</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
                <tr>
                    <td class="fw-bold">{{ $f['expediente'] }}</td>
                    <td class="fw-bold">{{ $f['cliente_nombre'] }}</td>
                    <td>{{ $f['identificacion'] }}</td>
                    <td>{{ $f['lote_codigo'] }}</td>
                    <td class="text-end fw-bold">${{ number_format($f['precio_venta'], 2) }}</td>
                    <td class="text-end fw-bold" style="color: #059669;">${{ number_format($f['total_abonado'], 2) }}</td>
                    <td class="text-end fw-bold" style="color: #dc2626;">${{ number_format($f['saldo_restante'], 2) }}</td>
                    <td class="text-center">{{ $f['cuotas_pagadas'] }}/{{ $f['cuotas_totales'] }}</td>
                    <td class="text-center">
                        @if($f['cuotas_mora'] > 0)
                            <span style="color: #dc2626; font-weight: bold;">Mora ({{ $f['cuotas_mora'] }})</span>
                        @elseif($f['estado_contrato'] === 'Finalizado')
                            <span style="color: #059669; font-weight: bold;">Cancelado</span>
                        @elseif($f['estado_contrato'] === 'Rescindido')
                            <span style="color: #475569; font-weight: bold;">Rescindido</span>
                        @else
                            <span style="color: #059669;">Al Día</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="4">TOTALES GENERALES:</td>
                <td class="text-end">${{ number_format($totalPrecioVentas, 2) }}</td>
                <td class="text-end" style="color: #059669;">${{ number_format($totalAbonadoGeneral, 2) }}</td>
                <td class="text-end" style="color: #dc2626;">${{ number_format($totalSaldoGeneral, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Control de Lotificaciones.
    </div>

</body>
</html>
