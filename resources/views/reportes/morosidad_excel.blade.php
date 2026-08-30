<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Morosidad</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="9" style="background-color: #991b1b; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center;">
                REPORTE DE MOROSIDAD Y ANTIGÜEDAD DE SALDOS
            </th>
        </tr>
        <tr>
            <td colspan="9" style="background-color: #fee2e2; text-align: center;">
                {{ $nombreProyecto }} - Generado el: {{ $generadoEl }}
            </td>
        </tr>
        <tr>
            <td colspan="3" style="font-weight: bold;">Clientes en Mora: {{ $totalClientesMora }}</td>
            <td colspan="3" style="font-weight: bold;">Capital Vencido: ${{ number_format($totalCapitalVencido, 2) }}</td>
            <td colspan="3" style="font-weight: bold; color: red;">Deuda Exigible: ${{ number_format($totalDeudaExigible, 2) }}</td>
        </tr>
        <thead>
            <tr style="background-color: #fca5a5; font-weight: bold;">
                <th>Expediente</th>
                <th>Cliente</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Proyecto</th>
                <th>Lote</th>
                <th>Cuotas Vencidas</th>
                <th>Capital Vencido ($)</th>
                <th>Mora Acumulada ($)</th>
                <th>Total Exigible ($)</th>
                <th>Días de Atraso</th>
                <th>Rango</th>
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
                    <td style="text-align: center;">{{ $f['cuotas_vencidas_count'] }}</td>
                    <td style="text-align: right;">{{ number_format($f['monto_cuotas_vencidas'], 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($f['mora_acumulada'], 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($f['total_deuda_vencida'], 2, '.', '') }}</td>
                    <td style="text-align: center;">{{ $f['max_dias_retraso'] }}</td>
                    <td>{{ $f['bucket_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #fee2e2; font-weight: bold;">
                <td colspan="7">TOTALES</td>
                <td style="text-align: right;">{{ number_format($totalCapitalVencido, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($totalMoraAcumulada, 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($totalDeudaExigible, 2, '.', '') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
