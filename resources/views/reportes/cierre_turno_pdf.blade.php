<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Cierre de Turno</title>
<style>
    @page { margin: 25px 30px; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        color: #2e2e2e;
        font-size: 11px;
    }
    .header-table {
        width: 100%;
        margin-bottom: 20px;
    }
    .header-table td {
        vertical-align: middle;
    }
    .logo-container {
        width: 25%;
    }
    .logo-container img {
        width: 100px;
    }
    .title-container {
        width: 75%;
        text-align: right;
        background-color: #d8e4fc;
        padding: 10px;
        border-radius: 4px;
    }
    .title-container h1 {
        margin: 0;
        font-size: 18px;
        color: #1c3666;
        text-transform: uppercase;
    }
    .title-container h2 {
        margin: 5px 0 0;
        font-size: 14px;
        color: #2e2e2e;
    }
    .title-container .timestamp {
        font-size: 10px;
        font-style: italic;
        color: #1c3666;
        margin-top: 5px;
    }
    
    .summary-section {
        width: 100%;
        margin-bottom: 20px;
        text-align: right;
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
    }
    .summary-table td {
        padding: 2px 0;
        font-size: 11px;
    }
    .summary-label {
        font-weight: bold;
        color: #1c3666;
        text-align: right;
        width: 80%;
    }
    .summary-value {
        font-weight: bold;
        color: #000;
        text-align: right;
        width: 20%;
    }
    
    .table-title {
        font-weight: bold;
        font-size: 11px;
        font-style: italic;
        color: #6a1b9a;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border: 1px solid #333;
    }
    .data-table th {
        border-bottom: 1px solid #333;
        padding: 6px;
        text-align: left;
        font-size: 10px;
        color: #666;
        font-weight: bold;
    }
    .data-table td {
        padding: 5px 6px;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .data-table .num-col {
        text-align: right;
    }
    .data-table .total-row td {
        font-weight: bold;
        color: #1c3666;
        border-top: 1px solid #333;
        border-bottom: none;
    }
    
    .signatures {
        width: 100%;
        margin-top: 60px;
    }
    .signatures td {
        width: 50%;
        text-align: center;
        font-weight: bold;
        font-size: 11px;
    }
    .signatures .line {
        border-top: 1px solid #000;
        width: 70%;
        margin: 0 auto 5px auto;
    }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-container">
                <!-- We will use a generic text if no logo -->
                <div style="font-size:16px; font-weight:bold; text-align:center;">
                    <i style="color:#2ca02c;">LOTIFICACIÓN</i><br>
                    {{ $lotificacionNombre ?? 'SISTEMA' }}
                </div>
            </td>
            <td class="title-container">
                <h1>LOTIFICACION {{ $lotificacionNombre ?? 'SISTEMA' }}</h1>
                <h2>Reporte de cierre de caja - {{ $fechaFormateada }}</h2>
                <div class="timestamp">Generado a las {{ $horaGeneracion }}</div>
            </td>
        </tr>
    </table>

    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-label" style="color:#0073e6;">Saldo anterior:</td>
                <td class="summary-value" style="color:#0073e6;">{{ number_format($saldoInicial, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="color:#0073e6;">Abonos en efectivo:</td>
                <td class="summary-value" style="color:#0073e6;">{{ number_format($totalEfectivo, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="color:#6a1b9a;">Ha ingresado a caja (Saldo anterior + Efectivo de hoy):</td>
                <td class="summary-value" style="color:#6a1b9a;">{{ number_format($saldoInicial + $totalEfectivo, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="color:#0073e6;">Salidas de hoy:</td>
                <td class="summary-value" style="color:#0073e6;">{{ number_format($totalSalidas, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="color:#0073e6;">Existencia en caja:</td>
                <td class="summary-value" style="color:#0073e6;">{{ number_format($saldoFinalCaja, 2) }}</td>
            </tr>
            <tr><td colspan="2" style="height: 10px;"></td></tr>
            <tr>
                <td class="summary-label" style="color:#0073e6;">Abonos por transferencias:</td>
                <td class="summary-value" style="color:#0073e6;">{{ number_format($totalTransferencias, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="color:#e65c00;">Monto total abonado el día de hoy (Efectivo + transferencias):</td>
                <td class="summary-value" style="color:#e65c00;">{{ number_format($totalEfectivo + $totalTransferencias, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- CUADRO 1: EFECTIVO -->
    <div class="table-title">CUADRO 1 <br> ABONOS EN EFECTIVO</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre del Cliente</th>
                <th>N° Lote</th>
                <th>N° Bloque</th>
                <th class="num-col">Abonado</th>
                <th>Hora</th>
                <th>Ref.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($abonosEfectivo as $abono)
            <tr>
                <td>{{ $abono['cliente'] }}</td>
                <td>{{ $abono['lotes'] }}</td>
                <td>{{ $abono['bloques'] }}</td>
                <td class="num-col">{{ number_format($abono['monto'], 2) }}</td>
                <td>{{ $abono['hora'] }}</td>
                <td>{{ $abono['referencia'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; font-style:italic;">No hay abonos en efectivo registrados.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3">TOTAL REGISTRADO EN EFECTIVO</td>
                <td class="num-col">{{ number_format($totalEfectivo, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <!-- CUADRO 2: TRANSFERENCIAS -->
    <div class="table-title">CUADRO 2 <br> TRANSFERENCIAS / DEPÓSITOS</div>
    <table class="data-table" style="background-color: #e5e4e2;">
        <thead>
            <tr>
                <th>Nombre del Cliente</th>
                <th>N° Lote</th>
                <th>N° Bloque</th>
                <th class="num-col">Abonado</th>
                <th>Detalles del deposito</th>
                <th>Ref.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($abonosTransferencia as $abono)
            <tr>
                <td>{{ $abono['cliente'] }}</td>
                <td>{{ $abono['lotes'] }}</td>
                <td>{{ $abono['bloques'] }}</td>
                <td class="num-col">{{ number_format($abono['monto'], 2) }}</td>
                <td>{{ $abono['cuenta_destino'] }} ({{ $abono['metodo_pago'] }})</td>
                <td style="color:#6a1b9a; font-weight:bold;">{{ $abono['referencia'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; font-style:italic;">No hay transferencias registradas.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="color:#1c3666;">TOTAL REGISTRADO EN TRANSFERENCIAS.</td>
                <td class="num-col" style="color:#1c3666;">{{ number_format($totalTransferencias, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                Elaborado y firmado por :<br>
                {{ strtoupper($cajero) }}
            </td>
            <td>
                <div class="line"></div>
                Recibido, aprobado y firmado por:
            </td>
        </tr>
    </table>

</body>
</html>
