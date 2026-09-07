<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Auditoría y Recaudación Financiera</title>
<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
    th { background: #1a56db; color: #ffffff; font-weight: bold; padding: 6px 8px; border: 1px solid #cccccc; text-align: left; }
    td { padding: 5px 8px; border: 1px solid #cccccc; }
    .rfx-titulo { font-size: 14px; font-weight: bold; color: #1a56db; }
    .rfx-subtitulo { color: #555555; font-size: 10px; }
    .rfx-seccion { background: #e5edff; font-weight: bold; font-size: 11px; color: #1a56db; }
    .rfx-seccion-danger { background: #fee2e2; font-weight: bold; font-size: 11px; color: #b91c1c; }
    .rfx-label { background: #f3f4f6; font-weight: bold; }
    .rfx-num { mso-number-format: "#,##0.00"; text-align: right; }
    .rfx-center { text-align: center; }
    .rfx-success { color: #059669; font-weight: bold; }
    .rfx-danger { color: #dc2626; font-weight: bold; }
</style>
</head>
<body>

<table>
    <!-- Encabezado General (Sin combinar celdas para no afectar selección de columnas) -->
    <tr><td class="rfx-titulo">REPORTE DE AUDITOR&Iacute;A Y RECAUDACI&Oacute;N FINANCIERA</td></tr>
    <tr><td class="rfx-subtitulo">Proyecto: {{ $etiquetaProyecto }} | Periodo: {{ $etiquetaPeriodo }} | Emisi&oacute;n: {{ $generadoEl }} | Auditor: {{ $generadoPor }}</td></tr>
    <tr><td></td></tr>

    <!-- 1. Resumen Financiero y Flujo Neto -->
    <tr><td class="rfx-seccion">1. RESUMEN FINANCIERO Y FLUJO NETO</td><td class="rfx-seccion">VALOR</td></tr>
    <tr>
        <td class="rfx-label">Ingresos Brutos Recaudados</td>
        <td class="rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Devoluciones por Rescisi&oacute;n</td>
        <td class="rfx-num rfx-danger">-{{ number_format($totalDevolucionesRescisiones, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Recaudaci&oacute;n Neta Real</td>
        <td class="rfx-num rfx-success">{{ number_format($recaudacionNeta, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Recibos Emitidos</td>
        <td class="rfx-center">{{ $cantidadAbonos }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Clientes Aportantes</td>
        <td class="rfx-center">{{ $clientesUnicos }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Ticket Promedio por Recibo</td>
        <td class="rfx-num">{{ number_format($ticketPromedio, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Recaudaci&oacute;n Bancarizada (Transferencias/Dep&oacute;sitos)</td>
        <td class="rfx-num">{{ number_format($totalBancos, 2, '.', '') }}</td>
    </tr>
    <tr>
        <td class="rfx-label">Recaudaci&oacute;n Efectivo en Caja</td>
        <td class="rfx-num">{{ number_format($totalEfectivo, 2, '.', '') }}</td>
    </tr>
    <tr><td></td></tr>

    <!-- 2. Desglose por Concepto Contable -->
    <tr>
        <th class="rfx-seccion">2. CONCEPTO DE COBRO</th>
        <th>Recibos</th>
        <th class="rfx-num">Monto ($ USD)</th>
        <th>% Participaci&oacute;n</th>
    </tr>
    @foreach($desgloseConceptos as $dc)
        <tr>
            <td>{{ $dc['concepto'] }}</td>
            <td class="rfx-center">{{ $dc['cantidad'] }}</td>
            <td class="rfx-num rfx-success">{{ number_format($dc['monto'], 2, '.', '') }}</td>
            <td class="rfx-center">{{ $dc['porcentaje'] }}%</td>
        </tr>
    @endforeach
    <tr>
        <td class="rfx-label">TOTAL RECAUDADO</td>
        <td class="rfx-label rfx-center">{{ $cantidadAbonos }}</td>
        <td class="rfx-label rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
        <td class="rfx-label rfx-center">100.0%</td>
    </tr>
    <tr><td></td></tr>

    <!-- 3. Desglose por Método de Pago -->
    <tr>
        <th class="rfx-seccion">3. M&Eacute;TODO / CANAL DE PAGO</th>
        <th>Recibos</th>
        <th class="rfx-num">Monto ($ USD)</th>
        <th>% Participaci&oacute;n</th>
    </tr>
    @foreach($desgloseMetodos as $dm)
        <tr>
            <td>{{ $dm['metodo'] }}</td>
            <td class="rfx-center">{{ $dm['cantidad'] }}</td>
            <td class="rfx-num">{{ number_format($dm['monto'], 2, '.', '') }}</td>
            <td class="rfx-center">{{ $dm['porcentaje'] }}%</td>
        </tr>
    @endforeach
    <tr>
        <td class="rfx-label">TOTAL CONCILIADO</td>
        <td class="rfx-label rfx-center">{{ $cantidadAbonos }}</td>
        <td class="rfx-label rfx-num">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
        <td class="rfx-label rfx-center">100.0%</td>
    </tr>
    <tr><td></td></tr>

    @if(count($filasRescisiones) > 0)
    <!-- 4. Registro de Rescisiones y Devoluciones Contables -->
    <tr><td class="rfx-seccion-danger">4. REGISTRO DE RESCISIONES Y DEVOLUCIONES CONTABLES (NO AFECTAN CAJA OPERATIVA)</td></tr>
    <tr>
        <th style="background: #b91c1c;">C&oacute;digo</th>
        <th style="background: #b91c1c;">Fecha</th>
        <th style="background: #b91c1c;">Hora</th>
        @if($esGlobal)
            <th style="background: #b91c1c;">Proyecto</th>
        @endif
        <th style="background: #b91c1c;">Cliente</th>
        <th style="background: #b91c1c;">Identificaci&oacute;n</th>
        <th style="background: #b91c1c;">Tipo</th>
        <th style="background: #b91c1c;">Lotes Desistidos</th>
        <th style="background: #b91c1c;">Tratamiento Contable</th>
        <th style="background: #b91c1c;">Motivo</th>
        <th style="background: #b91c1c;">Registrado por</th>
        <th class="rfx-num" style="background: #b91c1c;">Monto Devoluci&oacute;n ($)</th>
    </tr>
    @foreach($filasRescisiones as $fr)
    <tr>
        <td class="rfx-center"><strong>{{ $fr['codigo'] }}</strong></td>
        <td class="rfx-center">{{ $fr['fecha'] }}</td>
        <td class="rfx-center">{{ $fr['hora'] }}</td>
        @if($esGlobal)
            <td>{{ $fr['proyecto'] }}</td>
        @endif
        <td><strong>{{ $fr['cliente'] }}</strong></td>
        <td>{{ $fr['identificacion'] }}</td>
        <td>{{ $fr['tipo'] }}</td>
        <td>{{ $fr['lotes_afectados'] }}</td>
        <td>{{ $fr['destino_label'] }}</td>
        <td>{{ $fr['comentario'] }}</td>
        <td>{{ $fr['cajero'] }}</td>
        <td class="rfx-num rfx-danger">{{ $fr['monto_devuelto'] > 0 ? '-' . number_format($fr['monto_devuelto'], 2, '.', '') : '0.00' }}</td>
    </tr>
    @endforeach
    <tr>
        <td class="rfx-label">TOTAL DEVOLUCIONES POR RESCISI&Oacute;N</td>
        @for($k = 0; $k < ($esGlobal ? 10 : 9); $k++)
            <td class="rfx-label"></td>
        @endfor
        <td class="rfx-label rfx-num rfx-danger">-{{ number_format($totalDevolucionesRescisiones, 2, '.', '') }}</td>
    </tr>
    <tr><td></td></tr>
    @endif

    <!-- 5. Detalle de Recaudación y Cobranzas -->
    <tr><td class="rfx-seccion">{{ count($filasRescisiones) > 0 ? '5' : '4' }}. PLANILLA DE DETALLE DE RECAUDACI&Oacute;N Y COBRANZAS</td></tr>
    <tr>
        <th>N&deg; Recibo</th>
        <th>Fecha</th>
        <th>Hora</th>
        @if($esGlobal)
            <th>Proyecto</th>
        @endif
        <th>Cliente</th>
        <th>Identificaci&oacute;n</th>
        <th>Expediente</th>
        <th>Inmueble / Lote(s)</th>
        <th>Concepto</th>
        <th>M&eacute;todo de Pago</th>
        <th>Ref. Bancaria</th>
        <th>Cajero</th>
        <th class="rfx-num">Monto ($ USD)</th>
    </tr>
    @forelse ($filasAbonos as $f)
        <tr>
            <td class="rfx-center"><strong>{{ $f['recibo_codigo'] }}</strong></td>
            <td class="rfx-center">{{ $f['fecha'] }}</td>
            <td class="rfx-center">{{ $f['hora'] }}</td>
            @if($esGlobal)
                <td>{{ $f['proyecto'] }}</td>
            @endif
            <td><strong>{{ $f['cliente'] }}</strong></td>
            <td>{{ $f['identificacion'] }}</td>
            <td>{{ $f['expediente'] }}</td>
            <td>{{ $f['bloques'] }} - {{ $f['lotes'] }}</td>
            <td>{{ $f['tipo'] }}</td>
            <td>{{ $f['metodo'] }}</td>
            <td>{{ $f['referencia'] }}</td>
            <td>{{ $f['cajero'] }}</td>
            <td class="rfx-num rfx-success">{{ number_format($f['monto'], 2, '.', '') }}</td>
        </tr>
    @empty
        <tr>
            <td>No se registraron cobros en el periodo seleccionado.</td>
            @for($k = 0; $k < ($esGlobal ? 12 : 11); $k++)
                <td></td>
            @endfor
        </tr>
    @endforelse
    @if(count($filasAbonos) > 0)
    <tr>
        <td class="rfx-label">TOTAL GENERAL RECAUDADO ({{ count($filasAbonos) }} RECIBOS)</td>
        @for($k = 0; $k < ($esGlobal ? 11 : 10); $k++)
            <td class="rfx-label"></td>
        @endfor
        <td class="rfx-label rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
    </tr>
    @endif
</table>

</body>
</html>
