<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Morosidad y Cartera Vencida</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #1e293b; line-height: 1.3; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #dc2626; padding-bottom: 8px; }
        .logo { max-height: 45px; max-width: 150px; object-fit: contain; }
        .title { font-size: 15px; font-weight: bold; color: #dc2626; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #64748b; margin: 2px 0 0; }
        
        .kpi-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .kpi-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 4px; padding: 6px 8px; text-align: center; }
        .kpi-title { font-size: 8px; font-weight: bold; color: #991b1b; text-transform: uppercase; margin-bottom: 2px; }
        .kpi-val { font-size: 11px; font-weight: bold; color: #dc2626; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data-table th { background: #991b1b; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 5px 3px; text-align: left; }
        table.data-table td { font-size: 8.5px; padding: 4px 3px; border-bottom: 1px solid #fee2e2; }
        table.data-table tr:nth-child(even) { background-color: #fff5f5; }
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
                <h1 class="title">Reporte de Morosidad y Antigüedad de Cartera</h1>
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
                    <div class="kpi-title">1 a 30 Días</div>
                    <div class="kpi-val">{{ $resumenBuckets['1_30']['count'] }} clientes</div>
                    <div style="font-size: 8px;">${{ number_format($resumenBuckets['1_30']['total'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-box">
                    <div class="kpi-title">31 a 60 Días</div>
                    <div class="kpi-val">{{ $resumenBuckets['31_60']['count'] }} clientes</div>
                    <div style="font-size: 8px;">${{ number_format($resumenBuckets['31_60']['total'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-box">
                    <div class="kpi-title">61 a 90 Días</div>
                    <div class="kpi-val">{{ $resumenBuckets['61_90']['count'] }} clientes</div>
                    <div style="font-size: 8px;">${{ number_format($resumenBuckets['61_90']['total'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="background: #fee2e2; border-color: #ef4444;">
                    <div class="kpi-title" style="color: #7f1d1d;">+90 Días (Crítico)</div>
                    <div class="kpi-val" style="color: #7f1d1d;">{{ $resumenBuckets['mas_90']['count'] }} clientes</div>
                    <div style="font-size: 8px;">${{ number_format($resumenBuckets['mas_90']['total'], 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Expediente</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Lote</th>
                <th class="text-center">Cuotas Venc.</th>
                <th class="text-end">Capital Venc. ($)</th>
                <th class="text-end">Mora ($)</th>
                <th class="text-end">Total Exigible ($)</th>
                <th class="text-center">Días Atraso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
                <tr>
                    <td class="fw-bold">{{ $f['expediente'] }}</td>
                    <td class="fw-bold">{{ $f['cliente_nombre'] }}</td>
                    <td>{{ $f['telefono'] }}</td>
                    <td>{{ $f['lote_codigo'] }}</td>
                    <td class="text-center fw-bold">{{ $f['cuotas_vencidas_count'] }}</td>
                    <td class="text-end">${{ number_format($f['monto_cuotas_vencidas'], 2) }}</td>
                    <td class="text-end">${{ number_format($f['mora_acumulada'], 2) }}</td>
                    <td class="text-end fw-bold" style="color: #991b1b;">${{ number_format($f['total_deuda_vencida'], 2) }}</td>
                    <td class="text-center fw-bold">{{ $f['max_dias_retraso'] }} d</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #fee2e2; font-weight: bold;">
                <td colspan="5">TOTALES CARTERA EN MORA:</td>
                <td class="text-end">${{ number_format($totalCapitalVencido, 2) }}</td>
                <td class="text-end">${{ number_format($totalMoraAcumulada, 2) }}</td>
                <td class="text-end" style="color: #991b1b;">${{ number_format($totalDeudaExigible, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Control de Lotificaciones.
    </div>

</body>
</html>
