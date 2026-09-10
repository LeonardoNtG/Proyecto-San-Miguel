<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario de Lotes</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.3; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; }
        .logo { max-height: 50px; max-width: 160px; object-fit: contain; }
        .title { font-size: 16px; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 11px; color: #64748b; margin: 2px 0 0; }
        
        .kpi-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .kpi-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; text-align: center; }
        .kpi-title { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .kpi-val { font-size: 12px; font-weight: bold; color: #0f172a; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background: #0f172a; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 6px 4px; text-align: left; }
        table.data-table td { font-size: 9px; padding: 5px 4px; border-bottom: 1px solid #e2e8f0; }
        table.data-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-disp { background: #d1fae5; color: #065f46; }
        .badge-res { background: #fef3c7; color: #92400e; }
        .badge-ven { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 20px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

    <table style="width: 100%;" class="header">
        <tr>
            <td style="width: 70%; vertical-align: middle;">
                <h1 class="title">Reporte Oficial de Inventario y Lotes</h1>
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
            <td style="width: 25%; padding-right: 5px;">
                <div class="kpi-box">
                    <div class="kpi-title">Total Lotes</div>
                    <div class="kpi-val">{{ number_format($totalLotes) }}</div>
                    <div style="font-size: 8px; color: #64748b;">${{ number_format($valorTotal, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 5px;">
                <div class="kpi-box">
                    <div class="kpi-title">Disponibles</div>
                    <div class="kpi-val" style="color: #059669;">{{ number_format($totalDisponibles) }}</div>
                    <div style="font-size: 8px; color: #64748b;">${{ number_format($valorDisponible, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 5px;">
                <div class="kpi-box">
                    <div class="kpi-title">Reservados</div>
                    <div class="kpi-val" style="color: #d97706;">{{ number_format($totalReservados) }}</div>
                    <div style="font-size: 8px; color: #64748b;">${{ number_format($valorReservado, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Vendidos ({{ $porcentajeOcupacion }}%)</div>
                    <div class="kpi-val" style="color: #dc2626;">{{ number_format($totalVendidos) }}</div>
                    <div style="font-size: 8px; color: #64748b;">${{ number_format($valorVendido, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Proyecto</th>
                <th>Bloque</th>
                <th>N° Lote</th>
                <th class="text-end">Área (m²)</th>
                <th class="text-end">Área (vrs²)</th>
                <th class="text-end">Precio Base ($)</th>
                <th class="text-center">Estado</th>
                <th>Cliente / Titular</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lotes as $lote)
                @php
                    $vrs2 = $lote->area_metros / 0.705;
                    $clienteTitular = '-';
                    if ($lote->estado === 'Vendido' && $lote->ventas->isNotEmpty()) {
                        $vActiva = $lote->ventas->first();
                        $clienteTitular = $vActiva->cliente ? $vActiva->cliente->nombres_apellidos : 'Cliente Desconocido';
                    }
                @endphp
                <tr>
                    <td>{{ $lote->bloque && $lote->bloque->lotificacion ? $lote->bloque->lotificacion->nombre : '-' }}</td>
                    <td class="fw-bold">Bloque {{ $lote->bloque ? $lote->bloque->nombre : '-' }}</td>
                    <td class="fw-bold">{{ $lote->numero_lote }}</td>
                    <td class="text-end">{{ number_format($lote->area_metros, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($vrs2, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($lote->precio_base, 2) }}</td>
                    <td class="text-center">
                        @if($lote->estado === 'Disponible')
                            <span class="badge badge-disp">Disponible</span>
                        @elseif($lote->estado === 'Reservado')
                            <span class="badge badge-res">Reservado</span>
                        @else
                            <span class="badge badge-ven">Vendido</span>
                        @endif
                    </td>
                    <td>{{ $clienteTitular }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="3">TOTALES GENERALES:</td>
                <td class="text-end">{{ number_format($areaM2Total, 2) }} m²</td>
                <td class="text-end">{{ number_format($areaVrsTotal, 2) }} vrs²</td>
                <td class="text-end">${{ number_format($valorTotal, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Control de Lotificaciones.
    </div>

</body>
</html>
