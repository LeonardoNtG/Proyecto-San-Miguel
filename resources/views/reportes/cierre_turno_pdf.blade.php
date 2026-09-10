<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Cierre de Caja</title>
<style>
    @page {
        margin: 22px 28px;
        size: letter portrait;
    }
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #1e293b;
        font-size: 9.5px;
        line-height: 1.35;
        margin: 0;
        padding: 0;
    }

    /* MARCA DE AGUA CORPORATIVA DISCRETA */
    .watermark {
        position: fixed;
        top: 25%;
        left: 0;
        right: 0;
        text-align: center;
        opacity: 0.12;
        z-index: -1000;
    }
    .watermark img {
        width: 400px;
        height: auto;
    }

    /* ENCABEZADO INSTITUCIONAL */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        table-layout: fixed;
    }
    .header-table td {
        vertical-align: middle;
        padding: 0;
    }
    .logo-box {
        width: 195px;
        text-align: left;
        vertical-align: middle;
        background-color: transparent;
        padding: 0;
    }
    .logo-box img {
        max-width: 190px;
        max-height: 85px;
        object-fit: contain;
    }
    .logo-placeholder {
        font-size: 9px;
        font-weight: bold;
        color: #64748b;
        letter-spacing: 0.5px;
        line-height: 14px;
        padding: 10px 0;
        text-transform: uppercase;
    }
    .header-title-td {
        padding-left: 14px;
        text-align: left;
    }
    .doc-main-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 3px 0;
    }
    .doc-meta {
        font-size: 9.5px;
        color: #334155;
        margin: 1px 0;
    }
    .doc-meta strong {
        color: #0f172a;
    }
    .report-number-box {
        width: 135px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background-color: transparent;
        text-align: center;
        padding: 6px 4px;
    }
    .report-number-label {
        font-size: 8px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: bold;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
    }
    .report-number-code {
        font-size: 11.5px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.5px;
    }

    /* BARRAS DE SECCIÓN */
    .section-header {
        background-color: rgba(26, 54, 93, 0.82);
        color: #ffffff;
        font-size: 9.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 5px 8px;
        border-radius: 3px 3px 0 0;
        margin-top: 10px;
    }

    /* TABLAS DE DATOS */
    .table-data {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        table-layout: fixed;
        background-color: transparent;
    }
    .table-data th {
        background-color: rgba(43, 76, 117, 0.78);
        color: #ffffff;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 5px 6px;
        border: 1px solid rgba(26, 54, 93, 0.4);
        text-align: left;
    }
    .table-data td {
        padding: 4px 6px;
        font-size: 8.5px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        word-wrap: break-word;
        background-color: transparent;
    }
    .table-data tr td {
        background-color: transparent;
    }
    .table-data .total-row td {
        background-color: transparent !important;
        font-weight: bold;
        color: #0f172a;
        font-size: 9px;
        border-top: 1.5px solid #64748b;
        border-bottom: 1.5px solid #64748b;
    }
    .table-data .empty-row td {
        text-align: center;
        color: #64748b;
        font-style: italic;
        padding: 8px;
        background-color: transparent;
    }

    /* RESUMEN FINANCIERO DEL DÍA */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        background-color: transparent;
    }
    .summary-table th {
        background-color: rgba(26, 54, 93, 0.82);
        color: #ffffff;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 5px 8px;
        border: 1px solid rgba(26, 54, 93, 0.5);
    }
    .summary-table td {
        padding: 4px 8px;
        font-size: 9px;
        border: 1px solid #e2e8f0;
        background-color: transparent;
    }
    .summary-table tr td {
        background-color: transparent;
    }
    .summary-table .final-row td {
        background-color: transparent !important;
        font-weight: bold;
        color: #0f172a;
        font-size: 10px;
        border-top: 1.5px solid #64748b;
        border-bottom: 1.5px solid #64748b;
    }

    /* BADGES Y FORMATOS */
    .badge-lote {
        font-weight: 700;
        color: #0f172a;
    }
    .date-transf {
        font-weight: 700;
        color: #0369a1;
    }
    .date-pago {
        color: #334155;
    }
    .ref-code {
        font-family: 'Courier New', Courier, monospace;
        font-weight: bold;
        color: #0f172a;
        font-size: 8px;
    }

    /* OBSERVACIONES */
    .obs-container {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 6px 8px;
        background-color: transparent;
        min-height: 38px;
        font-size: 8.5px;
        color: #334155;
        margin-bottom: 14px;
    }

    /* FIRMAS */
    .signatures-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 22px;
        table-layout: fixed;
    }
    .signatures-table td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 20px;
    }
    .signature-line {
        border-top: 1px solid #0f172a;
        width: 80%;
        margin: 0 auto 4px auto;
    }
    .signature-role {
        font-size: 8.5px;
        color: #64748b;
        margin-bottom: 2px;
    }
    .signature-name {
        font-size: 9.5px;
        font-weight: bold;
        color: #0f172a;
        text-transform: uppercase;
    }

    .num-col {
        text-align: right;
    }
    .center-col {
        text-align: center;
    }
    
    /* REGLAS DE PAGINACIÓN */
    tr {
        page-break-inside: avoid;
    }
    .signatures-table {
        page-break-inside: avoid;
    }
    .section-header {
        page-break-after: avoid;
    }
</style>
</head>
<body>

    {{-- MARCA DE AGUA --}}
    @if(!empty($logoBase64))
    <div class="watermark">
        <img src="{{ $logoBase64 }}">
    </div>
    @endif

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td style="width: 195px;">
                <div class="logo-box">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @else
                        <div class="logo-placeholder">
                            {{ strtoupper($lotificacionNombre ?? 'PROYECTO') }}
                        </div>
                    @endif
                </div>
            </td>
            <td class="header-title-td">
                <div class="doc-main-title">REPORTE DE CIERRE DE CAJA</div>
                <div class="doc-meta"><strong>Lotificación:</strong> {{ $lotificacionNombre ?? 'Nombre del Proyecto' }}</div>
                <div class="doc-meta"><strong>Fecha:</strong> {{ $fechaTexto ?? $fechaFormateada }}</div>
                <div class="doc-meta"><strong>Generado:</strong> {{ $horaGeneracion }}</div>
            </td>
            <td style="width: 140px; text-align: right;">
                <div class="report-number-box">
                    <div class="report-number-label">No. de reporte:</div>
                    <div class="report-number-code">{{ $codigoReporte ?? ('CC-' . str_replace(['/', '-'], '', $fechaFormateada)) }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- 1. RESUMEN DEL DÍA --}}
    <div class="section-header">
        RESUMEN DEL DÍA
    </div>
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 70%; text-align: left;">CONCEPTO</th>
                <th style="width: 30%; text-align: right;">MONTO (U$)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Saldo anterior</td>
                <td class="num-col">{{ ($esProyectoSinSaldoAnterior ?? false) ? '0.00' : number_format($saldoInicial, 2) }}</td>
            </tr>
            <tr>
                <td>Ingresos en efectivo</td>
                <td class="num-col">{{ number_format($totalEfectivo, 2) }}</td>
            </tr>
            <tr>
                <td>Ingresos por transferencias / depósitos</td>
                <td class="num-col">{{ number_format($totalTransferencias, 2) }}</td>
            </tr>
            <tr>
                <td>Egresos (Salidas de caja)</td>
                <td class="num-col">{{ number_format($totalSalidas, 2) }}</td>
            </tr>
            <tr class="final-row">
                <td><strong>Saldo final en caja (Existencia en gaveta)</strong></td>
                <td class="num-col"><strong>{{ number_format($saldoFinalCaja, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total abonado del día (Efectivo + Bancos)</strong></td>
                <td class="num-col"><strong>{{ number_format($totalEfectivo + $totalTransferencias, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    {{-- 2. DETALLE DE ABONOS EN EFECTIVO --}}
    <div class="section-header">
        ABONOS EN EFECTIVO
    </div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 4%;" class="center-col">#</th>
                <th style="width: 31%;">Cliente</th>
                <th style="width: 14%;">Lote(s)</th>
                <th style="width: 12%;" class="num-col">Monto</th>
                <th style="width: 16%;">Forma de pago</th>
                <th style="width: 9%;" class="center-col">Hora</th>
                <th style="width: 14%;">Recibo / Ref.</th>
            </tr>
        </thead>
        <tbody>
            @php $idxEf = 1; @endphp
            @forelse($abonosEfectivo as $abono)
            <tr>
                <td class="center-col">{{ $idxEf++ }}</td>
                <td><strong>{{ $abono['cliente'] }}</strong></td>
                <td><span class="badge-lote">{{ $abono['lotes_texto'] ?? ('Lote ' . $abono['lotes']) }}</span></td>
                <td class="num-col"><strong>${{ number_format($abono['monto'], 2) }}</strong></td>
                <td>{{ $abono['metodo_pago'] ?? 'Efectivo' }}</td>
                <td class="center-col">{{ $abono['hora'] }}</td>
                <td><span class="ref-code">{{ $abono['numero_recibo'] ?? $abono['referencia'] }}</span></td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="7">No hay abonos en efectivo registrados.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">TOTAL EFECTIVO:</td>
                <td class="num-col"><strong>${{ number_format($totalEfectivo, 2) }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    {{-- 3. DETALLE DE TRANSFERENCIAS / DEPÓSITOS --}}
    <div class="section-header">
        TRANSFERENCIAS / DEPÓSITOS
    </div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 4%;" class="center-col">#</th>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 11%;">Lote(s)</th>
                <th style="width: 11%;" class="num-col">Monto</th>
                <th style="width: 11%;" class="center-col">F. Transf.</th>
                <th style="width: 11%;" class="center-col">F. Registro</th>
                <th style="width: 15%;">Banco / Cuenta destino</th>
                <th style="width: 12%;">Referencia</th>
            </tr>
        </thead>
        <tbody>
            @php $idxTr = 1; @endphp
            @forelse($abonosTransferencia as $abono)
            <tr>
                <td class="center-col">{{ $idxTr++ }}</td>
                <td><strong>{{ $abono['cliente'] }}</strong></td>
                <td><span class="badge-lote">{{ $abono['lotes_texto'] ?? ('Lote ' . $abono['lotes']) }}</span></td>
                <td class="num-col"><strong>${{ number_format($abono['monto'], 2) }}</strong></td>
                <td class="center-col"><span class="date-transf">{{ $abono['fecha_transferencia'] }}</span></td>
                <td class="center-col"><span class="date-pago">{{ $abono['fecha_pago'] }}</span></td>
                <td>{{ $abono['cuenta_destino'] }}</td>
                <td><span class="ref-code">{{ $abono['referencia'] }}</span></td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="8">No hay transferencias registradas.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">TOTAL TRANSFERENCIAS:</td>
                <td class="num-col"><strong>${{ number_format($totalTransferencias, 2) }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tbody>
    </table>

    {{-- 4. DETALLE DE RESCISIONES DE CONTRATOS (SI EXISTEN) --}}
    @if(!empty($rescisionesData) && count($rescisionesData) > 0)
    <div class="section-header">
        RESCISIONES DE CONTRATOS
    </div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 4%;" class="center-col">#</th>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 15%;">Lote(s) Rescindido(s)</th>
                <th style="width: 12%;" class="num-col">Monto</th>
                <th style="width: 22%;">Tipo / Aplicación</th>
                <th style="width: 22%;">Motivo / Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @php $idxRes = 1; @endphp
            @foreach($rescisionesData as $resc)
            <tr>
                <td class="center-col">{{ $idxRes++ }}</td>
                <td><strong>{{ $resc['cliente'] }}</strong></td>
                <td><span class="badge-lote" style="color: #b91c1c;">{{ $resc['lotes_afectados'] }}</span></td>
                <td class="num-col"><strong>${{ number_format($resc['monto_abonos_lote'], 2) }}</strong></td>
                <td>
                    {{ $resc['destino_texto'] }}
                    @if($resc['destino_abonos'] === 'acreditar_otro_lote')
                        <span style="font-size:7.5px; color:#15803d; font-weight:bold;">(Transf: ${{ number_format($resc['monto_transferido'], 2) }})</span>
                    @elseif($resc['destino_abonos'] === 'devolucion_efectivo')
                        <span style="font-size:7.5px; color:#b91c1c; font-weight:bold;">(Devol: ${{ number_format($resc['monto_devuelto'], 2) }})</span>
                    @endif
                </td>
                <td style="font-size: 8px;">{{ $resc['comentario'] ?: 'Sin observaciones' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">TOTAL RESCISIONES:</td>
                <td class="num-col"><strong>${{ number_format($totalRescisiones ?? 0, 2) }}</strong></td>
                <td colspan="2" style="font-size: 8px; color: #475569; font-style: italic;">* Movimiento informativo: No suma ni resta a la existencia en caja diaria</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- 5. OBSERVACIONES --}}
    <div class="section-header">
        OBSERVACIONES
    </div>
    <div class="obs-container">
        @if(!empty($comentario))
            <strong>Justificación / Nota de Cierre:</strong> {{ $comentario }}
        @else
            <em>Sin observaciones adicionales registradas en este cierre.</em>
        @endif
    </div>

    {{-- 6. FIRMAS --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-role">Elaborado por:</div>
                <div class="signature-name">{{ strtoupper($cajero) }}</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-role">Recibido, aprobado y firmado por:</div>
                <div class="signature-name">&nbsp;</div>
            </td>
        </tr>
    </table>

</body>
</html>
