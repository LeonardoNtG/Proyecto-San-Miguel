<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Financiero</title>
<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
    th { background: #4e73df; color: #ffffff; font-weight: bold; padding: 6px 8px; border: 1px solid #cccccc; }
    td { padding: 5px 8px; border: 1px solid #cccccc; }
    .rfx-titulo { font-size: 16px; font-weight: bold; }
    .rfx-subtitulo { color: #555555; }
    .rfx-resumen-label { font-weight: bold; background: #f8f9fc; }
    .rfx-ingresos { color: #1a9c6c; font-weight: bold; }
    .rfx-gastos { color: #c0392b; font-weight: bold; }
    .rfx-balance { color: #2e59d9; font-weight: bold; }
    .rfx-seccion { background: #eaeefb; font-weight: bold; font-size: 13px; }
    .rfx-seccion-caja { background: #e5f7ef; font-weight: bold; font-size: 13px; }
    .rfx-saldo-anterior { background: #fdf6e3; font-weight: bold; color: #8a6d00; }
    .rfx-num { mso-number-format: "#,##0.00"; }
</style>
</head>
<body>

<table>
    <tr><td class="rfx-titulo" colspan="8">Reporte Financiero</td></tr>
    <tr><td class="rfx-subtitulo" colspan="8">{{ $etiquetaPeriodo }} &mdash; Generado el {{ $generadoEl }}</td></tr>
    <tr><td colspan="8">&nbsp;</td></tr>

    <tr>
        <td class="rfx-saldo-anterior" colspan="8">
            Saldo Anterior (sin cerrar): {{ number_format($saldoAnterior, 2, '.', '') }}
            &nbsp;&mdash;&nbsp;
            Total Disponible (Saldo Anterior + Ingresos del Periodo): {{ number_format($totalConSaldoAnterior, 2, '.', '') }}
        </td>
    </tr>
    <tr><td colspan="8">&nbsp;</td></tr>

    <tr>
        <td class="rfx-resumen-label">Total Ingresos</td>
        <td class="rfx-ingresos">{{ number_format($totalIngresos, 2, '.', '') }}</td>
        <td class="rfx-resumen-label">Total Gastos</td>
        <td class="rfx-gastos">{{ number_format($totalGastos, 2, '.', '') }}</td>
        <td class="rfx-resumen-label">Balance Neto</td>
        <td class="rfx-balance">{{ number_format($balanceNeto, 2, '.', '') }}</td>
        <td class="rfx-resumen-label">Clientes que Abonaron</td>
        <td>{{ $clientesAbonaron }}</td>
    </tr>
    <tr><td colspan="8">&nbsp;</td></tr>

    <tr><td class="rfx-seccion" colspan="8">Cuadro 1 - Abonos Registrados</td></tr>
    <tr>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Cliente</th>
        <th>N&deg; PV</th>
        <th>N&deg; Bloque</th>
        <th>N&deg; Lote(s)</th>
        <th>Tipo de Pago</th>
        <th>Abonado</th>
        <th>Referencia</th>
    </tr>
    @forelse ($filasAbonos as $fila)
        <tr>
            <td>{{ $fila['fecha'] }}</td>
            <td>{{ $fila['hora'] }}</td>
            <td>{{ $fila['cliente'] }}</td>
            <td>{{ $fila['pv'] }}</td>
            <td>{{ $fila['bloques'] }}</td>
            <td>{{ $fila['lotes'] }}</td>
            <td>{{ $fila['tipo'] }}</td>
            <td class="rfx-num">{{ number_format($fila['monto'], 2, '.', '') }}</td>
            <td>{{ $fila['referencia'] }}</td>
        </tr>
    @empty
        <tr><td colspan="9">No se registraron abonos en el periodo seleccionado.</td></tr>
    @endforelse
    <tr>
        <td class="rfx-resumen-label" colspan="7">TOTAL REGISTRADO EN ABONOS</td>
        <td class="rfx-resumen-label rfx-num">{{ number_format($totalIngresos, 2, '.', '') }}</td>
        <td></td>
    </tr>

    <tr><td colspan="9">&nbsp;</td></tr>

    <tr><td class="rfx-seccion" colspan="9">Cuadro 2 - Salidas / Gastos</td></tr>
    <tr>
        <th>Fecha</th>
        <th>Hora</th>
        <th colspan="5">Descripci&oacute;n</th>
        <th colspan="2">Monto</th>
    </tr>
    @forelse ($filasSalidas as $fila)
        <tr>
            <td>{{ $fila['fecha'] }}</td>
            <td>{{ $fila['hora'] }}</td>
            <td colspan="5">{{ $fila['descripcion'] }}</td>
            <td colspan="2" class="rfx-num">{{ number_format($fila['monto'], 2, '.', '') }}</td>
        </tr>
    @empty
        <tr><td colspan="9">No se registraron salidas en el periodo seleccionado.</td></tr>
    @endforelse
    <tr>
        <td class="rfx-resumen-label" colspan="7">TOTAL REGISTRADO EN SALIDAS</td>
        <td class="rfx-resumen-label rfx-num" colspan="2">{{ number_format($totalGastos, 2, '.', '') }}</td>
    </tr>

    <tr><td colspan="9">&nbsp;</td></tr>

    <tr><td class="rfx-seccion-caja" colspan="9">Cuadro 3 - Efectivo en Caja del Periodo</td></tr>
    <tr>
        <td colspan="7">Dinero Ingresado</td>
        <td class="rfx-num rfx-ingresos" colspan="2">{{ number_format($totalIngresos, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td colspan="7">Gastos</td>
        <td class="rfx-num rfx-gastos" colspan="2">- {{ number_format($totalGastos, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-resumen-label" colspan="7">EFECTIVO EN CAJA (Ingresado &minus; Gastos)</td>
        <td class="rfx-resumen-label rfx-num rfx-ingresos" colspan="2">{{ number_format($balanceNeto, 2, '.', '') }}</td>
    </tr>
</table>

</body>
</html>
