<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cartera de Clientes</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="10" style="background-color: #0f172a; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center;">
                REPORTE DE CARTERA DE CLIENTES, CONTRATOS Y ABONOS
            </th>
        </tr>
        <tr>
            <td colspan="10" style="background-color: #f1f5f9; text-align: center;">
                {{ $nombreProyecto }} - Generado el: {{ $generadoEl }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Contratos: {{ $totalContratos }}</td>
            <td colspan="3" style="font-weight: bold;">Venta Total: ${{ number_format($totalPrecioVentas, 2) }}</td>
            <td colspan="3" style="font-weight: bold; color: green;">Total Recaudado: ${{ number_format($totalAbonadoGeneral, 2) }}</td>
            <td colspan="2" style="font-weight: bold; color: red;">Saldo por Cobrar: ${{ number_format($totalSaldoGeneral, 2) }}</td>
        </tr>
        <thead>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <th>Expediente</th>
                <th>Cliente</th>
                <th>Identificación</th>
                <th>Teléfono</th>
                <th>Proyecto</th>
                <th>Lote</th>
                <th>Precio Venta ($)</th>
                <th>Total Abonado ($)</th>
                <th>Saldo Capital ($)</th>
                <th>Cuotas Pag/Tot</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
                <tr>
                    <td>{{ $f['expediente'] }}</td>
                    <td>{{ $f['cliente_nombre'] }}</td>
                    <td>{{ $f['identificacion'] }}</td>
                    <td>{{ $f['telefono'] }}</td>
                    <td>{{ $f['proyecto'] }}</td>
                    <td>{{ $f['lote_codigo'] }}</td>
                    <td style="text-align: right;">{{ number_format($f['precio_venta'], 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($f['total_abonado'], 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($f['saldo_restante'], 2, '.', '') }}</td>
                    <td style="text-align: center;">{{ $f['cuotas_pagadas'] }} / {{ $f['cuotas_totales'] }}</td>
                    <td style="text-align: center;">{{ $f['estado_cliente'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="6">TOTALES GENERALES</td>
                <td style="text-align: right;">{{ number_format($totalPrecioVentas, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($totalAbonadoGeneral, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($totalSaldoGeneral, 2, '.', '') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
