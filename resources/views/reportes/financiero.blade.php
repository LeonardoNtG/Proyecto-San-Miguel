@extends('template')

@section('titulo', 'Reporte de Recaudación y Auditoría Financiera')

@section('contenido')

<link rel="stylesheet" href="{{ asset('css/reporte_financiero.css') }}">

<style>
    .audit-kpi-card {
        background: #fff;
        border-radius: 8px;
        padding: 1.1rem 1.2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08);
        border-left: 4px solid #4e73df;
        transition: transform 0.2s ease;
    }
    .audit-kpi-card:hover {
        transform: translateY(-2px);
    }
    .badge-metodo-efectivo { background-color: #1cc88a; color: #fff; }
    .badge-metodo-transferencia { background-color: #4e73df; color: #fff; }
    .badge-metodo-deposito { background-color: #36b9cc; color: #fff; }
    .badge-metodo-cheque { background-color: #f6c23e; color: #333; }
    .matrix-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e3e6f0;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.06);
    }
    .matrix-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 0.75rem 1.25rem;
        font-weight: 700;
        font-size: 0.95rem;
    }
    .table-audit th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background-color: #f8f9fc;
        color: #4e73df;
        font-weight: 700;
        vertical-align: middle;
    }
    .table-audit td {
        font-size: 0.88rem;
        vertical-align: middle;
    }
    .text-mono {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
</style>

<div class="rf-page-header">
    <div>
        <h1 class="h3 text-gray-800 fw-bold mb-1">
            <i class="fas fa-file-invoice-dollar text-primary me-2"></i> Reporte de Recaudación y Auditoría Financiera
        </h1>
        <div class="rf-subtitulo">
            <span class="badge bg-{{ $esGlobal ? 'warning text-dark' : 'primary text-white' }} me-2 px-2 py-1">
                <i class="fas fa-project-diagram me-1"></i> {{ $etiquetaProyecto }}
            </span>
            <span class="text-secondary fw-semibold">{{ $etiquetaPeriodo }}</span> &middot; 
            <small class="text-muted"><i class="fas fa-clock me-1"></i> Generado el {{ $generadoEl }}</small>
        </div>
    </div>
    <div class="rf-acciones-exportar d-flex gap-2">
        <a href="{{ route('reportes.financiero.pdf', request()->query()) }}"
           data-rf-exportar data-rf-base="{{ route('reportes.financiero.pdf') }}"
           class="btn btn-danger shadow-sm px-3" target="_blank">
            <i class="fas fa-file-pdf me-1"></i> Exportar Informe PDF
        </a>
        <a href="{{ route('reportes.financiero.excel', request()->query()) }}"
           data-rf-exportar data-rf-base="{{ route('reportes.financiero.excel') }}"
           class="btn btn-success shadow-sm px-3">
            <i class="fas fa-file-excel me-1"></i> Exportar Cédula Excel
        </a>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILTROS DE AUDITORÍA --}}
{{-- ================================================= --}}
<div class="rf-filtros mb-4">
    <form id="rf-form-filtros" method="GET" action="{{ route('reportes.financiero') }}">
        <div class="row g-3 align-items-end">
            @if(isset($esAdmin) && $esAdmin)
            <div class="col-12 col-md-3">
                <label for="rf-proyecto" class="font-weight-bold text-primary"><i class="fas fa-building me-1"></i> Proyecto / Consolidado</label>
                <select id="rf-proyecto" name="proyecto_id" class="form-select border-primary">
                    <option value="actual" @selected($proyectoFiltro === 'actual')>Proyecto Actual ({{ $userLotificaciones->firstWhere('id', session('lotificacion_id'))->nombre ?? 'Activo' }})</option>
                    <option value="global" @selected($proyectoFiltro === 'global' || $proyectoFiltro === 'todos')>⭐ CONSOLIDADO GLOBAL (TODAS LAS LOTIFICACIONES)</option>
                    <optgroup label="Filtrar por Proyecto Específico">
                        @foreach ($proyectosDisponibles as $proy)
                            <option value="{{ $proy->id }}" @selected((string)$proyectoFiltro === (string)$proy->id)>{{ $proy->nombre }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            @endif

            <div class="col-6 col-md-{{ isset($esAdmin) && $esAdmin ? '2' : '3' }}">
                <label for="rf-periodo">Periodo Contable</label>
                <select id="rf-periodo" name="periodo" class="form-select">
                    <option value="hoy" @selected($periodo === 'hoy')>Solo el día de hoy</option>
                    <option value="dia" @selected($periodo === 'dia')>Día específico</option>
                    <option value="mes" @selected($periodo === 'mes')>Mes completo</option>
                    <option value="ytd" @selected($periodo === 'ytd')>Año acumulado (YTD)</option>
                    <option value="anio" @selected($periodo === 'anio')>Año calendario completo</option>
                </select>
            </div>

            <div class="col-6 col-md-2 {{ $periodo === 'dia' ? '' : 'd-none' }}" id="rf-grupo-fecha">
                <label for="rf-fecha">Fecha Específica</label>
                <input type="date" id="rf-fecha" name="fecha" class="form-control" value="{{ $fechaSeleccionada }}" max="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="col-6 col-md-2 {{ in_array($periodo, ['mes', 'anio', 'ytd']) ? '' : 'd-none' }}" id="rf-grupo-anio">
                <label for="rf-anio">Año Fiscal</label>
                <select id="rf-anio" name="anio" class="form-select">
                    @foreach ($aniosDisponibles as $anioOpcion)
                        <option value="{{ $anioOpcion }}" @selected($anio === $anioOpcion)>{{ $anioOpcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2 {{ $periodo === 'mes' ? '' : 'd-none' }}" id="rf-grupo-mes">
                <label for="rf-mes">Mes de Corte</label>
                @php
                    $nombresMeses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ];
                @endphp
                <select id="rf-mes" name="mes" class="form-select">
                    @foreach ($nombresMeses as $numero => $nombre)
                        <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-{{ isset($esAdmin) && $esAdmin ? '3' : '3' }}">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                    <i class="fas fa-filter me-1"></i> Actualizar Reporte
                </button>
            </div>
        </div>
    </form>
</div>

{{-- ================================================= --}}
{{-- KPIS DE CONTROL DE AUDITORÍA --}}
{{-- ================================================= --}}
<div class="row g-3 mb-4">
    <!-- Total Recaudado -->
    <div class="col-xl-3 col-md-6">
        <div class="audit-kpi-card" style="border-left-color: #1cc88a;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Ingresos Auditados</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($totalRecaudado, 2) }}</div>
                </div>
                <div class="text-success opacity-50"><i class="fas fa-dollar-sign fa-2x"></i></div>
            </div>
            <div class="mt-2 text-muted small"><i class="fas fa-check-circle text-success me-1"></i> Fondos 100% verificados</div>
        </div>
    </div>

    <!-- Transacciones -->
    <div class="col-xl-3 col-md-6">
        <div class="audit-kpi-card" style="border-left-color: #4e73df;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Recibos / Operaciones</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($cantidadAbonos) }}</div>
                </div>
                <div class="text-primary opacity-50"><i class="fas fa-receipt fa-2x"></i></div>
            </div>
            <div class="mt-2 text-muted small"><i class="fas fa-tag text-primary me-1"></i> Promedio: ${{ number_format($ticketPromedio, 2) }} / recibo</div>
        </div>
    </div>

    <!-- Clientes Únicos -->
    <div class="col-xl-3 col-md-6">
        <div class="audit-kpi-card" style="border-left-color: #36b9cc;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Clientes Aportantes</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($clientesUnicos) }}</div>
                </div>
                <div class="text-info opacity-50"><i class="fas fa-users fa-2x"></i></div>
            </div>
            <div class="mt-2 text-muted small"><i class="fas fa-user-check text-info me-1"></i> Contratos con recaudación activa</div>
        </div>
    </div>

    <!-- Canal Bancarizado vs Efectivo -->
    <div class="col-xl-3 col-md-6">
        <div class="audit-kpi-card" style="border-left-color: #f6c23e;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Bancarización vs Caja</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $porcentajeBancarizado }}% Bancos</div>
                </div>
                <div class="text-warning opacity-50"><i class="fas fa-university fa-2x"></i></div>
            </div>
            <div class="mt-2 text-muted small">
                <span class="text-primary fw-bold">${{ number_format($totalBancos, 2) }}</span> bancos &middot; 
                <span class="text-success fw-bold">${{ number_format($totalEfectivo, 2) }}</span> caja
            </div>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- MATRICES DE CONTROL FINANCIERO Y CONCILIACIÓN --}}
{{-- ================================================= --}}
<div class="row g-3 mb-4">
    <!-- Matriz por Concepto -->
    <div class="col-lg-6">
        <div class="matrix-card h-100">
            <div class="matrix-header d-flex justify-content-between align-items-center text-primary">
                <span><i class="fas fa-chart-pie me-2"></i> 1. Desglose por Concepto Contable</span>
                <span class="badge bg-primary text-white">{{ count($desgloseConceptos) }} Tipos</span>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover table-sm table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Concepto</th>
                            <th class="text-center">Recibos</th>
                            <th class="text-end">Monto ($ USD)</th>
                            <th class="text-end" style="width: 25%;">% Part.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($desgloseConceptos as $dc)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $dc['concepto'] }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $dc['cantidad'] }}</span></td>
                            <td class="text-end font-weight-bold text-success">${{ number_format($dc['monto'], 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-2 small fw-bold">{{ $dc['porcentaje'] }}%</span>
                                    <div class="progress" style="width: 55px; height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $dc['porcentaje'] }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Sin movimientos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>TOTAL RECAUDADO</td>
                            <td class="text-center">{{ number_format($cantidadAbonos) }}</td>
                            <td class="text-end text-success">${{ number_format($totalRecaudado, 2) }}</td>
                            <td class="text-end">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Matriz por Método de Pago -->
    <div class="col-lg-6">
        <div class="matrix-card h-100">
            <div class="matrix-header d-flex justify-content-between align-items-center text-info">
                <span><i class="fas fa-wallet me-2"></i> 2. Conciliación por Canal / Método de Pago</span>
                <span class="badge bg-info text-white">{{ count($desgloseMetodos) }} Canales</span>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover table-sm table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Canal / Método</th>
                            <th class="text-center">Recibos</th>
                            <th class="text-end">Monto ($ USD)</th>
                            <th class="text-end" style="width: 25%;">% Part.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($desgloseMetodos as $dm)
                        <tr>
                            <td>
                                @php
                                    $metodoStr = strtolower($dm['metodo']);
                                    $icono = 'fa-money-bill';
                                    $badgeClass = 'bg-secondary';
                                    if (str_contains($metodoStr, 'efectivo')) {
                                        $icono = 'fa-money-bill-wave text-success';
                                        $badgeClass = 'badge-metodo-efectivo';
                                    } elseif (str_contains($metodoStr, 'transferencia')) {
                                        $icono = 'fa-exchange-alt text-primary';
                                        $badgeClass = 'badge-metodo-transferencia';
                                    } elseif (str_contains($metodoStr, 'depósito') || str_contains($metodoStr, 'deposito')) {
                                        $icono = 'fa-university text-info';
                                        $badgeClass = 'badge-metodo-deposito';
                                    } elseif (str_contains($metodoStr, 'cheque')) {
                                        $icono = 'fa-money-check text-warning';
                                        $badgeClass = 'badge-metodo-cheque';
                                    }
                                @endphp
                                <i class="fas {{ $icono }} me-1"></i>
                                <span class="fw-semibold">{{ $dm['metodo'] }}</span>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $dm['cantidad'] }}</span></td>
                            <td class="text-end font-weight-bold text-primary">${{ number_format($dm['monto'], 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-2 small fw-bold">{{ $dm['porcentaje'] }}%</span>
                                    <div class="progress" style="width: 55px; height: 6px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $dm['porcentaje'] }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Sin movimientos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>TOTAL CONCILIADO</td>
                            <td class="text-center">{{ number_format($cantidadAbonos) }}</td>
                            <td class="text-end text-primary">${{ number_format($totalRecaudado, 2) }}</td>
                            <td class="text-end">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@if($esGlobal && count($desgloseProyectos) > 0)
{{-- ================================================= --}}
{{-- MATRIZ CONSOLIDADA POR PROYECTO --}}
{{-- ================================================= --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="matrix-card">
            <div class="matrix-header d-flex justify-content-between align-items-center text-dark">
                <span><i class="fas fa-layer-group me-2 text-warning"></i> 3. Distribución Consolidada por Proyecto / Lotificación</span>
                <span class="badge bg-warning text-dark">{{ count($desgloseProyectos) }} Proyectos con recaudación</span>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover table-sm table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Proyecto / Lotificación</th>
                            <th class="text-center">Clientes</th>
                            <th class="text-center">Recibos Emitidos</th>
                            <th class="text-end">Monto Recaudado ($ USD)</th>
                            <th class="text-end" style="width: 25%;">% Contribución</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($desgloseProyectos as $dp)
                        <tr>
                            <td class="fw-bold text-dark"><i class="fas fa-city text-primary me-2"></i> {{ $dp['proyecto'] }}</td>
                            <td class="text-center">{{ $dp['clientes'] }}</td>
                            <td class="text-center">{{ $dp['cantidad'] }}</td>
                            <td class="text-end font-weight-bold text-success">${{ number_format($dp['monto'], 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-2 small fw-bold">{{ $dp['porcentaje'] }}%</span>
                                    <div class="progress" style="width: 70px; height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $dp['porcentaje'] }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ================================================= --}}
{{-- LIBRO MAYOR DE AUDITORÍA / DETALLE DE TRANSACCIONES --}}
{{-- ================================================= --}}
<div class="card shadow-sm border-0 mb-5">
    <div class="card-header py-3 bg-white d-flex flex-wrap justify-content-between align-items-center border-bottom">
        <h5 class="m-0 font-weight-bold text-gray-800">
            <i class="fas fa-list-alt text-primary me-2"></i> Cédula de Detalle de Recaudación ({{ $etiquetaPeriodo }})
        </h5>
        <div class="d-flex align-items-center gap-3 mt-2 mt-md-0">
            <div class="input-group input-group-sm" style="width: 280px;">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="rf-buscador" class="form-control" placeholder="Buscar cliente, recibo, ref, lote...">
            </div>
            <span class="badge bg-success fs-6 px-3 py-2">
                Total: ${{ number_format($totalRecaudado, 2) }}
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-audit mb-0" id="rf-tabla-abonos">
            <thead>
                <tr>
                    <th class="text-center" style="width: 85px;">N° Recibo</th>
                    <th>Fecha & Hora</th>
                    @if($esGlobal)
                        <th>Proyecto</th>
                    @endif
                    <th>Cliente / Cédula</th>
                    <th>Expediente</th>
                    <th>Inmueble</th>
                    <th>Concepto</th>
                    <th>Canal / Método</th>
                    <th>Ref. Bancaria</th>
                    <th>Cajero</th>
                    <th class="text-end">Monto ($ USD)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filasAbonos as $fila)
                    <tr data-rf-fila>
                        <td class="text-center fw-bold text-primary text-mono">
                            {{ $fila['recibo_codigo'] }}
                        </td>
                        <td>
                            <span class="d-block fw-semibold text-dark">{{ $fila['fecha'] }}</span>
                            <small class="text-muted text-mono">{{ $fila['hora'] }}</small>
                        </td>
                        @if($esGlobal)
                            <td><span class="badge bg-dark text-white">{{ $fila['proyecto'] }}</span></td>
                        @endif
                        <td>
                            <strong class="text-dark d-block">{{ $fila['cliente'] }}</strong>
                            <small class="text-muted"><i class="fas fa-id-card me-1"></i> {{ $fila['identificacion'] }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border text-mono px-2 py-1">{{ $fila['expediente'] }}</span>
                        </td>
                        <td>
                            <span class="d-block small text-muted">Blq: <strong>{{ $fila['bloques'] }}</strong></span>
                            <span class="fw-bold text-dark">{{ $fila['lotes'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info text-white px-2 py-1">{{ $fila['tipo'] }}</span>
                        </td>
                        <td>
                            @php
                                $mStr = strtolower($fila['metodo']);
                                $bgBdg = 'bg-secondary';
                                if (str_contains($mStr, 'efectivo')) $bgBdg = 'bg-success';
                                elseif (str_contains($mStr, 'transferencia')) $bgBdg = 'bg-primary';
                                elseif (str_contains($mStr, 'depósito') || str_contains($mStr, 'deposito')) $bgBdg = 'bg-info';
                                elseif (str_contains($mStr, 'cheque')) $bgBdg = 'bg-warning text-dark';
                            @endphp
                            <span class="badge {{ $bgBdg }} px-2 py-1">{{ $fila['metodo'] }}</span>
                        </td>
                        <td>
                            <span class="text-mono small text-secondary">{{ $fila['referencia'] }}</span>
                        </td>
                        <td>
                            <small class="text-muted"><i class="fas fa-user-edit me-1"></i> {{ $fila['cajero'] }}</small>
                        </td>
                        <td class="text-end font-weight-bold text-success fs-6 text-mono">
                            ${{ number_format($fila['monto'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $esGlobal ? '11' : '10' }}" class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-400"></i>
                            No se registraron cobros ni recaudaciones en el periodo contable seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($filasAbonos) > 0)
            <tfoot class="table-light fw-bold border-top">
                <tr>
                    <td colspan="{{ $esGlobal ? '10' : '9' }}" class="text-end text-uppercase text-secondary">
                        TOTAL GENERAL RECAUDADO Y AUDITADO ({{ count($filasAbonos) }} OPERACIONES):
                    </td>
                    <td class="text-end text-success fs-5 text-mono">
                        ${{ number_format($totalRecaudado, 2) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Buscador en vivo en la tabla de auditoría
    const buscador = document.getElementById('rf-buscador');
    const filas = document.querySelectorAll('#rf-tabla-abonos tbody tr[data-rf-fila]');

    if (buscador) {
        buscador.addEventListener('input', function () {
            const termino = this.value.toLowerCase().trim();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(termino) ? '' : 'none';
            });
        });
    }

    // Toggle de visibilidad de fechas según selector de período
    const periodoSelect = document.getElementById('rf-periodo');
    const grupoFecha = document.getElementById('rf-grupo-fecha');
    const grupoAnio = document.getElementById('rf-grupo-anio');
    const grupoMes = document.getElementById('rf-grupo-mes');

    function actualizarVisibilidadFiltros() {
        const val = periodoSelect.value;
        if (grupoFecha) grupoFecha.classList.toggle('d-none', val !== 'dia');
        if (grupoAnio) grupoAnio.classList.toggle('d-none', !['mes', 'anio', 'ytd'].includes(val));
        if (grupoMes) grupoMes.classList.toggle('d-none', val !== 'mes');
    }

    if (periodoSelect) {
        periodoSelect.addEventListener('change', actualizarVisibilidadFiltros);
    }
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
