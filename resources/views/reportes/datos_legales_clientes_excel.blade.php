<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Expediente de Datos Legales para Promesas de Venta</title>
<style>
    table { border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 11px; }
    th { background: #1A237E; color: #ffffff; font-weight: bold; padding: 6px 8px; border: 1px solid #999999; text-align: left; }
    td { padding: 5px 8px; border: 1px solid #cccccc; vertical-align: middle; }
    .title-main { font-size: 16px; font-weight: bold; color: #1A237E; }
    .subtitle { color: #555555; font-size: 11px; }
    .section-header { background: #E8EAF6; font-weight: bold; font-size: 12px; color: #1A237E; }
    .kpi-label { background: #f8fafc; font-weight: bold; color: #334155; }
    .num-fmt { mso-number-format: "\$#,##0.00"; text-align: right; }
    .qty-fmt { mso-number-format: "#,##0.00"; text-align: right; }
    .text-center { text-align: center; }
    .text-mono { font-family: Consolas, monospace; }
    .fw-bold { font-weight: bold; }
    .highlight { color: #16a34a; font-weight: bold; }
</style>
</head>
<body>

<table>
    <tr>
        <td class="title-main" colspan="19">EXPEDIENTE DE DATOS LEGALES Y GENERALES DE LEY PARA PROMESAS DE VENTA</td>
    </tr>
    <tr>
        <td class="subtitle" colspan="19">
            Proyecto: {{ $nombreProyecto }} &mdash; Emisión: {{ $generadoEl }} &mdash; Total Contratos: {{ number_format($totalClientes) }} &mdash; Usuario: {{ auth()->user()->name ?? 'Sistema' }}
        </td>
    </tr>
    <tr><td colspan="19">&nbsp;</td></tr>

    <!-- Resumen Consolidado -->
    <tr>
        <td class="section-header" colspan="19">1. RESUMEN FINANCIERO Y CONTRACTUAL</td>
    </tr>
    <tr>
        <td class="kpi-label" colspan="2">Total Contratos Listados</td>
        <td class="text-center fw-bold" colspan="2">{{ number_format($totalClientes) }}</td>
        <td class="kpi-label" colspan="2">Valor Total Contratado</td>
        <td class="num-fmt fw-bold highlight" colspan="2">{{ number_format($totalPrecioVentas, 2, '.', '') }}</td>
        <td class="kpi-label" colspan="2">Total Primas Pagadas</td>
        <td class="num-fmt fw-bold" colspan="2">{{ number_format($totalPrimas, 2, '.', '') }}</td>
        <td class="kpi-label" colspan="2">Saldo Total a Financiar</td>
        <td class="num-fmt fw-bold" colspan="2">{{ number_format($totalSaldoFinanciar, 2, '.', '') }}</td>
        <td class="kpi-label" colspan="2">Área Total Vendida</td>
        <td class="qty-fmt fw-bold">{{ number_format($totalAreaM2, 2, '.', '') }} m²</td>
    </tr>
    <tr><td colspan="19">&nbsp;</td></tr>

    <!-- Tabla Detallada para Notarios -->
    <tr>
        <td class="section-header" colspan="19">2. DETALLE DE COMPRADORES, INMUEBLES Y TÉRMINOS NOTARIALES</td>
    </tr>
    <tr>
        <th>N° Expediente</th>
        <th>N° Promesa (PV)</th>
        <th>Fecha Venta</th>
        <th>Proyecto / Lotificación</th>
        <th>Comprador (Nombres y Apellidos)</th>
        <th>Cédula / Identificación</th>
        <th>Estado Civil</th>
        <th>Profesión / Oficio</th>
        <th>Teléfono</th>
        <th>Dirección Domiciliar</th>
        <th>Lote(s) Adquirido(s)</th>
        <th class="qty-fmt">Área (m²)</th>
        <th class="qty-fmt">Área Aprox (v²)</th>
        <th class="num-fmt">Precio Total ($)</th>
        <th class="num-fmt">Prima Pagada ($)</th>
        <th class="num-fmt">Saldo Financiado ($)</th>
        <th class="text-center">Plazo (Meses)</th>
        <th class="num-fmt">Cuota Mensual ($)</th>
        <th>Estado Contrato</th>
        <th>Beneficiario Final</th>
    </tr>

    @foreach($ventasData as $v)
    <tr>
        <td class="text-center">{{ $v['expediente_num'] }}</td>
        <td class="text-center fw-bold">{{ $v['pv_num'] }}</td>
        <td class="text-center">{{ $v['fecha_venta_fmt'] }}</td>
        <td>{{ $v['proyecto_nombre'] }}</td>
        <td class="fw-bold">{{ $v['cliente_nombre'] }}</td>
        <td class="text-mono">{{ $v['identificacion'] }}</td>
        <td>{{ $v['estado_civil'] }}</td>
        <td>{{ $v['oficio'] }}</td>
        <td>{{ $v['telefono'] }}</td>
        <td>{{ $v['direccion'] }}</td>
        <td class="fw-bold">{{ $v['lotes_texto'] }}</td>
        <td class="qty-fmt">{{ number_format($v['area_metros'], 2, '.', '') }}</td>
        <td class="qty-fmt">{{ number_format($v['area_varas'], 2, '.', '') }}</td>
        <td class="num-fmt fw-bold">{{ number_format($v['precio_final'], 2, '.', '') }}</td>
        <td class="num-fmt highlight">{{ number_format($v['prima_pagada'], 2, '.', '') }}</td>
        <td class="num-fmt">{{ number_format($v['saldo_financiar'], 2, '.', '') }}</td>
        <td class="text-center">{{ $v['plazo_meses'] }}</td>
        <td class="num-fmt fw-bold">{{ number_format($v['cuota_mensual'], 2, '.', '') }}</td>
        <td class="text-center">{{ $v['estado_contrato'] }}</td>
        <td>{{ $v['beneficiario_final'] ? $v['beneficiario_final'] . ($v['nota_beneficiario'] ? ' (' . $v['nota_beneficiario'] . ')' : '') : 'N/A' }}</td>
    </tr>
    @endforeach

    <!-- Fila de Totales -->
    <tr style="background: #f1f5f9; font-weight: bold;">
        <td colspan="11" class="text-center">TOTALES GENERALES</td>
        <td class="qty-fmt">{{ number_format($totalAreaM2, 2, '.', '') }}</td>
        <td class="qty-fmt">{{ number_format($totalAreaM2 * 1.4198, 2, '.', '') }}</td>
        <td class="num-fmt">{{ number_format($totalPrecioVentas, 2, '.', '') }}</td>
        <td class="num-fmt highlight">{{ number_format($totalPrimas, 2, '.', '') }}</td>
        <td class="num-fmt">{{ number_format($totalSaldoFinanciar, 2, '.', '') }}</td>
        <td colspan="4">&nbsp;</td>
    </tr>
</table>

</body>
</html>
