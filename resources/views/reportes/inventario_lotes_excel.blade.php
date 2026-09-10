<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventario de Lotes</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="8" style="background-color: #0f172a; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center;">
                REPORTE DE INVENTARIO Y DISPONIBILIDAD DE LOTES
            </th>
        </tr>
        <tr>
            <td colspan="8" style="background-color: #f1f5f9; text-align: center;">
                {{ $nombreProyecto }} - Generado el: {{ $generadoEl }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Total Lotes: {{ $totalLotes }}</td>
            <td colspan="2" style="font-weight: bold; color: green;">Disponibles: {{ $totalDisponibles }} (${{ number_format($valorDisponible, 2) }})</td>
            <td colspan="2" style="font-weight: bold; color: orange;">Reservados: {{ $totalReservados }} (${{ number_format($valorReservado, 2) }})</td>
            <td colspan="2" style="font-weight: bold; color: red;">Vendidos: {{ $totalVendidos }} (${{ number_format($valorVendido, 2) }})</td>
        </tr>
        <thead>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <th>Proyecto</th>
                <th>Bloque</th>
                <th>N° Lote</th>
                <th>Área (m²)</th>
                <th>Área (vrs²)</th>
                <th>Precio Base ($)</th>
                <th>Estado</th>
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
                    <td>Bloque {{ $lote->bloque ? $lote->bloque->nombre : '-' }}</td>
                    <td>{{ $lote->numero_lote }}</td>
                    <td style="text-align: right;">{{ number_format($lote->area_metros, 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($vrs2, 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($lote->precio_base, 2, '.', '') }}</td>
                    <td style="text-align: center;">{{ $lote->estado }}</td>
                    <td>{{ $clienteTitular }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="3">TOTALES</td>
                <td style="text-align: right;">{{ number_format($areaM2Total, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($areaVrsTotal, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($valorTotal, 2, '.', '') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
