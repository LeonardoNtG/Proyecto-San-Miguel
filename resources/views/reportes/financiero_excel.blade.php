<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cédula de Auditoría y Recaudación Financiera</title>
<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
    th { background: #1a56db; color: #ffffff; font-weight: bold; padding: 6px 8px; border: 1px solid #cccccc; text-align: left; }
    td { padding: 5px 8px; border: 1px solid #cccccc; }
    .rfx-titulo { font-size: 15px; font-weight: bold; color: #1a56db; }
    .rfx-subtitulo { color: #555555; font-size: 11px; }
    .rfx-seccion { background: #e5edff; font-weight: bold; font-size: 12px; color: #1a56db; }
    .rfx-label { background: #f3f4f6; font-weight: bold; }
    .rfx-num { mso-number-format: "#,##0.00"; text-align: right; }
    .rfx-center { text-align: center; }
    .rfx-success { color: #059669; font-weight: bold; }
</style>
</head>
<body>

<table>
    <tr><td class="rfx-titulo" colspan="{{ $esGlobal ? '12' : '11' }}">C&Eacute;DULA DE AUDITOR&Iacute;A Y RECAUDACI&Oacute;N FINANCIERA</td></tr>
    <tr><td class="rfx-subtitulo" colspan="{{ $esGlobal ? '12' : '11' }}">Proyecto: {{ $etiquetaProyecto }} &mdash; Periodo: {{ $etiquetaPeriodo }} &mdash; Emisi&oacute;n: {{ $generadoEl }} &mdash; Auditor: {{ $generadoPor }}</td></tr>
    <tr><td colspan="{{ $esGlobal ? '12' : '11' }}">&nbsp;</td></tr>

    <!-- Resumen Ejecutivo -->
    <tr><td class="rfx-seccion" colspan="{{ $esGlobal ? '12' : '11' }}">1. RESUMEN EJECUTIVO DE AUDITOR&Iacute;A</td></tr>
    <tr>
        <td class="rfx-label">Total Recaudado Auditado</td>
        <td class="rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
        <td class="rfx-label">Recibos Emitidos</td>
        <td class="rfx-center">{{ $cantidadAbonos }}</td>
        <td class="rfx-label">Clientes Aportantes</td>
        <td class="rfx-center">{{ $clientesUnicos }}</td>
        <td class="rfx-label">Ticket Promedio</td>
        <td class="rfx-num">{{ number_format($ticketPromedio, 2, '.', '') }}</td>
        <td class="rfx-label">Canal Bancarizado</td>
        <td colspan="{{ $esGlobal ? '3' : '2' }}">{{ $porcentajeBancarizado }}% Bancos ({{ number_format($totalBancos, 2, '.', '') }}) / {{ $porcentajeEfectivo }}% Caja ({{ number_format($totalEfectivo, 2, '.', '') }})</td>
    </tr>
    <tr><td colspan="{{ $esGlobal ? '12' : '11' }}">&nbsp;</td></tr>

    <!-- Matrices Resumen -->
    <tr><td class="rfx-seccion" colspan="{{ $esGlobal ? '12' : '11' }}">2. DESGLOSE POR CONCEPTO CONTABLE Y M&Eacute;TODO DE PAGO</td></tr>
    <tr>
        <th colspan="3">Concepto de Cobro</th>
        <th>Recibos</th>
        <th class="rfx-num">Monto ($ USD)</th>
        <th>% Part.</th>
        <th colspan="2">M&eacute;todo / Canal</th>
        <th>Recibos</th>
        <th class="rfx-num">Monto ($ USD)</th>
        <th colspan="{{ $esGlobal ? '2' : '1' }}">% Part.</th>
    </tr>
    @php
        $maxRows = max(count($desgloseConceptos), count($desgloseMetodos));
    @endphp
    @for($i = 0; $i < $maxRows; $i++)
        @php
            $dc = $desgloseConceptos[$i] ?? null;
            $dm = $desgloseMetodos[$i] ?? null;
        @endphp
        <tr>
            <td colspan="3">{{ $dc ? $dc['concepto'] : '' }}</td>
            <td class="rfx-center">{{ $dc ? $dc['cantidad'] : '' }}</td>
            <td class="rfx-num rfx-success">{{ $dc ? number_format($dc['monto'], 2, '.', '') : '' }}</td>
            <td class="rfx-center">{{ $dc ? $dc['porcentaje'] . '%' : '' }}</td>

            <td colspan="2">{{ $dm ? $dm['metodo'] : '' }}</td>
            <td class="rfx-center">{{ $dm ? $dm['cantidad'] : '' }}</td>
            <td class="rfx-num">{{ $dm ? number_format($dm['monto'], 2, '.', '') : '' }}</td>
            <td colspan="{{ $esGlobal ? '2' : '1' }}" class="rfx-center">{{ $dm ? $dm['porcentaje'] . '%' : '' }}</td>
        </tr>
    @endfor
    <tr>
        <td class="rfx-label" colspan="3">TOTAL RECAUDADO</td>
        <td class="rfx-label rfx-center">{{ $cantidadAbonos }}</td>
        <td class="rfx-label rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
        <td class="rfx-label rfx-center">100.0%</td>
        <td class="rfx-label" colspan="2">TOTAL CONCILIADO</td>
        <td class="rfx-label rfx-center">{{ $cantidadAbonos }}</td>
        <td class="rfx-label rfx-num">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
        <td colspan="{{ $esGlobal ? '2' : '1' }}" class="rfx-label rfx-center">100.0%</td>
    </tr>
    <tr><td colspan="{{ $esGlobal ? '12' : '11' }}">&nbsp;</td></tr>

    <!-- Detalle de Transacciones -->
    <tr><td class="rfx-seccion" colspan="{{ $esGlobal ? '12' : '11' }}">3. C&Eacute;DULA DE DETALLE DE RECAUDACI&Oacute;N Y COBRANZAS</td></tr>
    <tr>
        <th>N&deg; Recibo</th>
        <th>Fecha</th>
        <th>Hora</th>
        @if($esGlobal)
            <th>Proyecto</th>
        @endif
        <th>Cliente</th>
        <th>C&eacute;dula</th>
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
        <tr><td colspan="{{ $esGlobal ? '13' : '12' }}">No se registraron cobros en el periodo contable seleccionado.</td></tr>
    @endforelse
    @if(count($filasAbonos) > 0)
    <tr>
        <td class="rfx-label" colspan="{{ $esGlobal ? '12' : '11' }}">TOTAL GENERAL RECAUDADO Y AUDITADO ({{ count($filasAbonos) }} OPERACIONES)</td>
        <td class="rfx-label rfx-num rfx-success">{{ number_format($totalRecaudado, 2, '.', '') }}</td>
    </tr>
    @endif
</table>

</body>
</html>
