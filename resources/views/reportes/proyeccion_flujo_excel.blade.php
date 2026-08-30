<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proyección de Flujo</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="5" style="background-color: #0f172a; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center;">
                REPORTE DE PROYECCIÓN DE FLUJO Y RECAUDACIÓN FUTURA
            </th>
        </tr>
        <tr>
            <td colspan="5" style="background-color: #f1f5f9; text-align: center;">
                {{ $nombreProyecto }} - Horizonte: {{ $mesesProyeccion }} Meses - Generado el: {{ $generadoEl }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Recaudación Total: ${{ number_format($totalProyeccion, 2) }}</td>
            <td colspan="2" style="font-weight: bold;">Promedio Mensual: ${{ number_format($promedioMensual, 2) }}</td>
            <td style="font-weight: bold;">Cuotas: {{ $totalCuotasProyectadas }}</td>
        </tr>
        <thead>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <th>Mes / Período</th>
                <th>Cuotas Programadas</th>
                <th>Capital Esperado ($)</th>
                <th>Interés Esperado ($)</th>
                <th>Total Esperado ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mesesData as $m)
                <tr>
                    <td>{{ $m['mes_nombre'] }} {{ $m['anio'] }}</td>
                    <td style="text-align: center;">{{ $m['cuotas_cantidad'] }}</td>
                    <td style="text-align: right;">{{ number_format($m['capital_esperado'], 2, '.', '') }}</td>
                    <td style="text-align: right;">{{ number_format($m['interes_esperado'], 2, '.', '') }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($m['total_esperado'], 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td>TOTAL PROYECTADO</td>
                <td style="text-align: center;">{{ $totalCuotasProyectadas }}</td>
                <td style="text-align: right;">{{ number_format(array_sum(array_column($mesesData, 'capital_esperado')), 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format(array_sum(array_column($mesesData, 'interes_esperado')), 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($totalProyeccion, 2, '.', '') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
