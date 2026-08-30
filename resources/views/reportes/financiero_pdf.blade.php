<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe de Auditoría y Recaudación Financiera</title>
<style>
    @page { margin: 20px 25px; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        color: #2e2e2e;
        font-size: 9.5px;
        line-height: 1.3;
    }

    .header-container {
        border-bottom: 2px solid #1a56db;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-title {
        font-size: 15px;
        font-weight: bold;
        color: #1a56db;
        text-transform: uppercase;
        margin: 0 0 3px;
    }

    .header-subtitle {
        font-size: 10px;
        color: #4b5563;
    }

    .meta-box {
        text-align: right;
        font-size: 8.5px;
        color: #6b7280;
    }

    /* KPIs */
    table.kpi-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }

    table.kpi-table td {
        width: 25%;
        border: 1px solid #d1d5db;
        padding: 6px 8px;
        text-align: center;
        background-color: #f9fafb;
    }

    .kpi-label {
        font-size: 7.5px;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: bold;
        display: block;
        margin-bottom: 2px;
    }

    .kpi-val {
        font-size: 12px;
        font-weight: bold;
        color: #111827;
    }

    .kpi-val.success { color: #059669; }
    .kpi-val.primary { color: #1a56db; }

    /* Summary matrices */
    .section-title {
        font-size: 10.5px;
        font-weight: bold;
        color: #1f2937;
        margin: 10px 0 4px;
        border-left: 3px solid #1a56db;
        padding-left: 5px;
        text-transform: uppercase;
    }

    table.matrix-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    table.matrix-table th {
        background-color: #f3f4f6;
        border: 1px solid #d1d5db;
        padding: 4px 6px;
        font-size: 8px;
        text-transform: uppercase;
        color: #374151;
        font-weight: bold;
    }

    table.matrix-table td {
        border: 1px solid #d1d5db;
        padding: 4px 6px;
        font-size: 8.5px;
    }

    /* Detail table */
    table.detail-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }

    table.detail-table th {
        background-color: #1a56db;
        color: #ffffff;
        border: 1px solid #1a56db;
        padding: 4px 5px;
        font-size: 7.5px;
        text-transform: uppercase;
        font-weight: bold;
        text-align: left;
    }

    table.detail-table td {
        border: 1px solid #e5e7eb;
        padding: 4px 5px;
        font-size: 8px;
    }

    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .fw-bold { font-weight: bold; }
    .text-success { color: #059669; }

    tr.even { background-color: #f9fafb; }

    .signatures {
        width: 100%;
        margin-top: 25px;
        border-collapse: collapse;
    }

    .signatures td {
        width: 50%;
        text-align: center;
        padding: 0 35px;
        font-size: 8.5px;
    }

    .sign-line {
        border-top: 1px solid #374151;
        margin-top: 35px;
        padding-top: 4px;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="header-container">
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">Informe de Auditoría y Recaudación Financiera</div>
                <div class="header-subtitle">
                    <strong>Proyecto:</strong> {{ $etiquetaProyecto }} &nbsp;|&nbsp; 
                    <strong>Periodo:</strong> {{ $etiquetaPeriodo }}
                </div>
            </td>
            <td class="meta-box">
                <div><strong>Fecha Emisión:</strong> {{ $generadoEl }}</div>
                <div><strong>Auditado Por:</strong> {{ $generadoPor }}</div>
                <div><strong>Moneda:</strong> Dólares Americanos (USD)</div>
            </td>
        </tr>
    </table>
</div>

<!-- KPIs Ejecutivos -->
<table class="kpi-table">
    <tr>
        <td>
            <span class="kpi-label">Total Recaudado Auditado</span>
            <span class="kpi-val success">${{ number_format($totalRecaudado, 2) }}</span>
        </td>
        <td>
            <span class="kpi-label">Transacciones Procesadas</span>
            <span class="kpi-val primary">{{ number_format($cantidadAbonos) }} Recibos</span>
        </td>
        <td>
            <span class="kpi-label">Clientes Aportantes</span>
            <span class="kpi-val">{{ number_format($clientesUnicos) }} Clientes</span>
        </td>
        <td>
            <span class="kpi-label">Canal Bancarizado</span>
            <span class="kpi-val">{{ $porcentajeBancarizado }}% Bancos</span>
        </td>
    </tr>
</table>

<!-- Matrices Resumen en 2 Columnas -->
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 49%; vertical-align: top;">
            <div class="section-title">1. Desglose por Concepto Contable</div>
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="text-center">Rec.</th>
                        <th class="text-end">Monto ($)</th>
                        <th class="text-end">% Part.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($desgloseConceptos as $dc)
                    <tr>
                        <td>{{ $dc['concepto'] }}</td>
                        <td class="text-center">{{ $dc['cantidad'] }}</td>
                        <td class="text-end fw-bold text-success">${{ number_format($dc['monto'], 2) }}</td>
                        <td class="text-end">{{ $dc['porcentaje'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <td>TOTAL</td>
                        <td class="text-center">{{ number_format($cantidadAbonos) }}</td>
                        <td class="text-end text-success">${{ number_format($totalRecaudado, 2) }}</td>
                        <td class="text-end">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 49%; vertical-align: top;">
            <div class="section-title">2. Conciliación por Método de Pago</div>
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>Método / Canal</th>
                        <th class="text-center">Rec.</th>
                        <th class="text-end">Monto ($)</th>
                        <th class="text-end">% Part.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($desgloseMetodos as $dm)
                    <tr>
                        <td>{{ $dm['metodo'] }}</td>
                        <td class="text-center">{{ $dm['cantidad'] }}</td>
                        <td class="text-end fw-bold text-success">${{ number_format($dm['monto'], 2) }}</td>
                        <td class="text-end">{{ $dm['porcentaje'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <td>TOTAL</td>
                        <td class="text-center">{{ number_format($cantidadAbonos) }}</td>
                        <td class="text-end text-success">${{ number_format($totalRecaudado, 2) }}</td>
                        <td class="text-end">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>

@if($esGlobal && count($desgloseProyectos) > 0)
<div class="section-title">3. Distribución Consolidada por Proyecto</div>
<table class="matrix-table" style="margin-bottom: 12px;">
    <thead>
        <tr>
            <th>Proyecto / Lotificación</th>
            <th class="text-center">Clientes</th>
            <th class="text-center">Recibos</th>
            <th class="text-end">Monto Recaudado ($ USD)</th>
            <th class="text-end">% Contribución</th>
        </tr>
    </thead>
    <tbody>
        @foreach($desgloseProyectos as $dp)
        <tr>
            <td><strong>{{ $dp['proyecto'] }}</strong></td>
            <td class="text-center">{{ $dp['clientes'] }}</td>
            <td class="text-center">{{ $dp['cantidad'] }}</td>
            <td class="text-end fw-bold text-success">${{ number_format($dp['monto'], 2) }}</td>
            <td class="text-end">{{ $dp['porcentaje'] }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- Detalle Completo de Transacciones -->
<div class="section-title">{{ $esGlobal ? '4' : '3' }}. Planilla de Detalle de Recaudación y Cobranzas</div>
<table class="detail-table">
    <thead>
        <tr>
            <th class="text-center">N° Rec.</th>
            <th>Fecha / Hora</th>
            @if($esGlobal)
                <th>Proyecto</th>
            @endif
            <th>Cliente</th>
            <th>Identificación</th>
            <th>Expediente</th>
            <th>Lote(s)</th>
            <th>Concepto</th>
            <th>Método</th>
            <th>Referencia</th>
            <th>Cajero</th>
            <th class="text-end">Monto ($)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($filasAbonos as $index => $f)
        <tr class="{{ $index % 2 == 1 ? 'even' : '' }}">
            <td class="text-center fw-bold">{{ $f['recibo_codigo'] }}</td>
            <td>{{ $f['fecha'] }} <small style="color: #6b7280;">{{ $f['hora'] }}</small></td>
            @if($esGlobal)
                <td><strong>{{ $f['proyecto'] }}</strong></td>
            @endif
            <td><strong>{{ $f['cliente'] }}</strong></td>
            <td>{{ $f['identificacion'] }}</td>
            <td>{{ $f['expediente'] }}</td>
            <td>{{ $f['lotes'] }}</td>
            <td>{{ $f['tipo'] }}</td>
            <td>{{ $f['metodo'] }}</td>
            <td><small>{{ $f['referencia'] }}</small></td>
            <td><small>{{ $f['cajero'] }}</small></td>
            <td class="text-end fw-bold text-success">${{ number_format($f['monto'], 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ $esGlobal ? '12' : '11' }}" class="text-center" style="padding: 15px; color: #6b7280;">
                No se registraron cobros en el periodo seleccionado.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if(count($filasAbonos) > 0)
    <tfoot>
        <tr style="background-color: #f3f4f6; font-weight: bold;">
            <td colspan="{{ $esGlobal ? '11' : '10' }}" class="text-end">TOTAL RECAUDADO ({{ count($filasAbonos) }} REGISTROS):</td>
            <td class="text-end text-success">${{ number_format($totalRecaudado, 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<!-- Firmas de Auditoría -->
<table class="signatures">
    <tr>
        <td>
            <div class="sign-line">
                {{ $generadoPor }}<br>
                <small style="color: #6b7280; font-weight: normal;">Responsable de Recaudación / Caja</small>
            </div>
        </td>
        <td>
            <div class="sign-line">
                Auditoría / Gerencia Financiera<br>
                <small style="color: #6b7280; font-weight: normal;">Revisado y Conforme</small>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
