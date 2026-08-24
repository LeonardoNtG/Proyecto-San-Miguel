<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Financiero</title>
<style>
    @page { margin: 25px 30px; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        color: #2e2e2e;
        font-size: 11px;
    }

    h1 {
        text-align: center;
        font-size: 20px;
        margin: 0 0 4px;
        color: #2e2e2e;
    }

    .rfpdf-subtitulo {
        text-align: center;
        font-size: 12px;
        color: #555;
        margin-bottom: 14px;
    }

    table.rfpdf-resumen {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    table.rfpdf-resumen td {
        width: 20%;
        text-align: center;
        padding: 8px 6px;
        border: 1px solid #dcdfe6;
    }

    table.rfpdf-resumen .rfpdf-label {
        display: block;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #858796;
        margin-bottom: 3px;
    }

    table.rfpdf-resumen .rfpdf-valor {
        font-size: 14px;
        font-weight: bold;
    }

    .rfpdf-ingresos { color: #1a9c6c; }
    .rfpdf-gastos { color: #c0392b; }
    .rfpdf-balance { color: #2e59d9; }
    .rfpdf-neutro { color: #2e2e2e; }

    .rfpdf-cuadro-titulo {
        font-size: 13px;
        font-weight: bold;
        margin: 14px 0 6px;
        padding-bottom: 3px;
        border-bottom: 2px solid #4e73df;
    }

    .rfpdf-cuadro-titulo.rfpdf-gastos-titulo {
        border-bottom-color: #e74a3b;
    }

    .rfpdf-cuadro-titulo.rfpdf-caja-titulo {
        border-bottom-color: #1cc88a;
    }

    table.rfpdf-caja {
        width: 55%;
    }

    table.rfpdf-caja tfoot td {
        font-size: 12px;
        color: #0f8a5f;
    }

    table.rfpdf-tabla {
        width: 100%;
        border-collapse: collapse;
    }

    table.rfpdf-tabla th {
        background: #f8f9fc;
        border: 1px solid #dcdfe6;
        padding: 5px 6px;
        text-align: left;
        font-size: 9.5px;
        text-transform: uppercase;
        color: #555;
    }

    table.rfpdf-tabla td {
        border: 1px solid #dcdfe6;
        padding: 5px 6px;
        font-size: 10px;
    }

    table.rfpdf-tabla td.rfpdf-num,
    table.rfpdf-tabla th.rfpdf-num {
        text-align: right;
    }

    table.rfpdf-tabla tfoot td {
        font-weight: bold;
        background: #f8f9fc;
    }

    .rfpdf-vacio {
        text-align: center;
        color: #858796;
        font-style: italic;
        padding: 10px;
    }

    .rfpdf-firmas {
        width: 100%;
        margin-top: 40px;
    }

    .rfpdf-firmas td {
        width: 50%;
        text-align: center;
        padding-top: 4px;
        border-top: 1px solid #2e2e2e;
        font-size: 10px;
    }

    .rfpdf-firmas .rfpdf-espacio {
        height: 40px;
    }

    .rfpdf-pie {
        margin-top: 10px;
        text-align: right;
        font-size: 8.5px;
        color: #999;
    }

    .rfpdf-saldo-anterior {
        text-align: center;
        background: #fdf6e3;
        border: 1px solid #f0d78c;
        border-radius: 3px;
        padding: 6px 10px;
        margin-bottom: 12px;
        font-size: 10.5px;
    }

    .rfpdf-saldo-anterior strong {
        color: #8a6d00;
    }
</style>
</head>
<body>

    <h1>Reporte Financiero</h1>
    <div class="rfpdf-subtitulo">{{ $etiquetaPeriodo }}</div>

    <div class="rfpdf-saldo-anterior">
        Saldo Anterior (sin cerrar): <strong>${{ number_format($saldoAnterior, 2) }}</strong>
        &nbsp;&mdash;&nbsp;
        Total Disponible (Saldo Anterior + Ingresos del Periodo): <strong>${{ number_format($totalConSaldoAnterior, 2) }}</strong>
    </div>

    <table class="rfpdf-resumen">
        <tr>
            <td>
                <span class="rfpdf-label">Total Ingresos</span>
                <span class="rfpdf-valor rfpdf-ingresos">${{ number_format($totalIngresos, 2) }}</span>
            </td>
            <td>
                <span class="rfpdf-label">Total Gastos</span>
                <span class="rfpdf-valor rfpdf-gastos">${{ number_format($totalGastos, 2) }}</span>
            </td>
            <td>
                <span class="rfpdf-label">Balance Neto</span>
                <span class="rfpdf-valor rfpdf-balance">${{ number_format($balanceNeto, 2) }}</span>
            </td>
            <td>
                <span class="rfpdf-label">Clientes que Abonaron</span>
                <span class="rfpdf-valor rfpdf-neutro">{{ $clientesAbonaron }}</span>
            </td>
            <td>
                <span class="rfpdf-label">Abonos Registrados</span>
                <span class="rfpdf-valor rfpdf-neutro">{{ $cantidadAbonos }}</span>
            </td>
        </tr>
    </table>

    <div class="rfpdf-cuadro-titulo">Cuadro 1 — Abonos Registrados</div>
    <table class="rfpdf-tabla">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>N° Bloque</th>
                <th>N° Lote(s)</th>
                <th>Tipo</th>
                <th class="rfpdf-num">Abonado</th>
                <th>Ref.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filasAbonos as $fila)
                <tr>
                    <td>{{ $fila['fecha'] }}</td>
                    <td>{{ $fila['cliente'] }} ({{ $fila['pv'] }})</td>
                    <td>{{ $fila['bloques'] }}</td>
                    <td>{{ $fila['lotes'] }}</td>
                    <td>{{ $fila['tipo'] }}</td>
                    <td class="rfpdf-num">${{ number_format($fila['monto'], 2) }}</td>
                    <td>{{ $fila['referencia'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="rfpdf-vacio">No se registraron abonos en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
        @if ($filasAbonos->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">TOTAL REGISTRADO EN ABONOS</td>
                <td class="rfpdf-num">${{ number_format($totalIngresos, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="rfpdf-cuadro-titulo rfpdf-gastos-titulo">Cuadro 2 — Salidas / Gastos</div>
    <table class="rfpdf-tabla">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th class="rfpdf-num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filasSalidas as $fila)
                <tr>
                    <td>{{ $fila['fecha'] }}</td>
                    <td>{{ $fila['descripcion'] }}</td>
                    <td class="rfpdf-num">${{ number_format($fila['monto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="rfpdf-vacio">No se registraron salidas en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
        @if ($filasSalidas->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2">TOTAL REGISTRADO EN SALIDAS</td>
                <td class="rfpdf-num">${{ number_format($totalGastos, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="rfpdf-cuadro-titulo rfpdf-caja-titulo">Cuadro 3 — Efectivo en Caja del Periodo</div>
    <table class="rfpdf-tabla rfpdf-caja">
        <tbody>
            <tr>
                <td>Dinero Ingresado</td>
                <td class="rfpdf-num">${{ number_format($totalIngresos, 2) }}</td>
            </tr>
            <tr>
                <td>Gastos</td>
                <td class="rfpdf-num">- ${{ number_format($totalGastos, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>EFECTIVO EN CAJA (Ingresado − Gastos)</td>
                <td class="rfpdf-num">${{ number_format($balanceNeto, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="rfpdf-firmas">
        <tr>
            <td class="rfpdf-espacio"></td>
            <td class="rfpdf-espacio"></td>
        </tr>
        <tr>
            <td>Elaborado y firmado por</td>
            <td>Recibido, aprobado y firmado por</td>
        </tr>
    </table>

    <div class="rfpdf-pie">Generado el {{ $generadoEl }}</div>

</body>
</html>
