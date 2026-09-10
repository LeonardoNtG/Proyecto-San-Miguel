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

    /* Badges de Alta Legibilidad y Contraste */
    .badge-metodo {
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .badge-metodo-efectivo {
        background-color: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #86efac !important;
    }
    .badge-metodo-transferencia {
        background-color: #dbeafe !important;
        color: #1e40af !important;
        border: 1px solid #93c5fd !important;
    }
    .badge-metodo-deposito {
        background-color: #ccfbf1 !important;
        color: #0f766e !important;
        border: 1px solid #5eead4 !important;
    }
    .badge-metodo-cheque {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fcd34d !important;
    }
    .badge-metodo-default {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        border: 1px solid #cbd5e1 !important;
    }

    .badge-concepto {
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        display: inline-block;
        white-space: nowrap;
        background-color: #f1f5f9;
        color: #1e293b;
        border: 1px solid #cbd5e1;
    }
    .badge-concepto-cuota {
        background-color: #f0f9ff !important;
        color: #0369a1 !important;
        border: 1px solid #7dd3fc !important;
    }
    .badge-concepto-prima {
        background-color: #faf5ff !important;
        color: #6b21a8 !important;
        border: 1px solid #d8b4fe !important;
    }
    .badge-concepto-extra {
        background-color: #fff7ed !important;
        color: #c2410c !important;
        border: 1px solid #fdba74 !important;
    }

    .badge-expediente {
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 5px;
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border: 1px solid #94a3b8 !important;
    }

    .matrix-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.06);
    }
    .matrix-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #cbd5e1;
        padding: 0.75rem 1.25rem;
        font-weight: 700;
        font-size: 0.95rem;
    }
    .table-audit th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 800 !important;
        vertical-align: middle;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .table-audit td {
        font-size: 0.88rem;
        color: #0f172a !important;
        vertical-align: middle;
        border-color: #e2e8f0;
    }
    .table-audit tbody tr:hover {
        background-color: #f8fafc;
    }
    .badge-header-danger {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fca5a5 !important;
        font-weight: 800 !important;
        font-size: 0.92rem !important;
        padding: 0.4rem 0.85rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
    }
    .badge-header-success {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #86efac !important;
        font-weight: 800 !important;
        font-size: 0.92rem !important;
        padding: 0.4rem 0.85rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
    }
    .btn-danger-contrast {
        background-color: #dc2626 !important;
        border-color: #b91c1c !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .btn-danger-contrast:hover {
        background-color: #b91c1c !important;
        color: #ffffff !important;
    }
    .btn-success-contrast {
        background-color: #16a34a !important;
        border-color: #15803d !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .btn-success-contrast:hover {
        background-color: #15803d !important;
        color: #ffffff !important;
    }
    .text-mono {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    .text-muted-dark {
        color: #334155 !important;
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
           class="btn btn-danger-contrast shadow-sm px-3" target="_blank">
            <i class="fas fa-file-pdf me-1"></i> Exportar Informe PDF
        </a>
        <a href="{{ route('reportes.financiero.excel', request()->query()) }}"
           data-rf-exportar data-rf-base="{{ route('reportes.financiero.excel') }}"
           class="btn btn-success-contrast shadow-sm px-3">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
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
{{-- KPIS DE CONTROL DE RECAUDACIÓN REAL --}}
{{-- ================================================= --}}
<div class="row g-3 mb-4">
    <!-- Total Recaudado -->
    <div class="col-xl-3 col-md-6">
        <div class="audit-kpi-card" style="border-left-color: #1cc88a;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Ingresos Recaudados</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($totalRecaudado, 2) }}</div>
                </div>
                <div class="text-success opacity-50"><i class="fas fa-dollar-sign fa-2x"></i></div>
            </div>
            <div class="mt-2 text-muted small"><i class="fas fa-check-circle text-success me-1"></i> Fondos reales en bancos y caja</div>
        </div>
    </div>

    <!-- Transacciones / Recibos -->
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
                                    $icono = 'fa-money-bill-wave';
                                    $bClass = 'badge-metodo-default';
                                    if (str_contains($metodoStr, 'efectivo')) {
                                        $icono = 'fa-money-bill-wave';
                                        $bClass = 'badge-metodo-efectivo';
                                    } elseif (str_contains($metodoStr, 'transferencia')) {
                                        $icono = 'fa-exchange-alt';
                                        $bClass = 'badge-metodo-transferencia';
                                    } elseif (str_contains($metodoStr, 'depósito') || str_contains($metodoStr, 'deposito')) {
                                        $icono = 'fa-university';
                                        $bClass = 'badge-metodo-deposito';
                                    } elseif (str_contains($metodoStr, 'cheque')) {
                                        $icono = 'fa-money-check';
                                        $bClass = 'badge-metodo-cheque';
                                    }
                                @endphp
                                <span class="badge-metodo {{ $bClass }}">
                                    <i class="fas {{ $icono }}"></i> {{ $dm['metodo'] }}
                                </span>
                            </td>
                            <td class="text-center"><span class="badge-expediente">{{ $dm['cantidad'] }}</span></td>
                            <td class="text-end font-weight-bold" style="color: #047857 !important; font-size: 0.92rem;">${{ number_format($dm['monto'], 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-2 small fw-bold text-dark">{{ $dm['porcentaje'] }}%</span>
                                    <div class="progress" style="width: 55px; height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $dm['porcentaje'] }}%"></div>
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
{{-- REGISTRO DE RESCISIONES Y OBLIGACIONES CONTABLES --}}
{{-- ================================================= --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header py-3 bg-white d-flex flex-wrap justify-content-between align-items-center border-bottom gap-2">
        <div>
            <h5 class="m-0 font-weight-bold text-danger">
                <i class="fas fa-undo-alt me-2"></i> Registro de Rescisiones y Compromisos de Devolución Contable ({{ $etiquetaPeriodo }})
            </h5>
            <small class="text-muted">
                Registro de contratos rescindidos, lotes liberados a inventario y obligaciones contables de reintegro.
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge-header-danger">
                <i class="fas fa-undo-alt me-1"></i> Devoluciones: ${{ number_format($totalDevolucionesRescisiones, 2) }}
            </span>
            <a href="{{ route('rescisiones.index') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i> Ver Historial
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="alert alert-light border-0 border-start border-4 border-warning m-3 mb-2 p-2 small text-dark bg-light">
            <i class="fas fa-info-circle text-warning me-1"></i> <strong>Nota Contable / Auditoría:</strong> Las devoluciones por rescisión no se liquidan de la caja operativa diaria de las recepciones. Constituyen pasivos/compromisos a ser ejecutados y conciliados por el departamento de Contabilidad/Tesorería durante el mes fiscal.
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-audit mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;"># Rescisión</th>
                        <th>Fecha & Hora</th>
                        @if($esGlobal)
                            <th>Proyecto</th>
                        @endif
                        <th>Cliente / Identificación</th>
                        <th>Expediente</th>
                        <th>Tipo</th>
                        <th>Lotes Desistidos (Disponibles)</th>
                        <th>Destino Contable</th>
                        <th>Motivo / Justificación</th>
                        <th>Registrado por</th>
                        <th class="text-end">Monto Devolución ($ USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filasRescisiones as $fr)
                    <tr>
                        <td class="text-center fw-bold text-danger text-mono">
                            {{ $fr['codigo'] }}
                        </td>
                        <td>
                            <span class="d-block fw-bold text-dark">{{ $fr['fecha'] }}</span>
                            <small class="text-muted-dark text-mono fw-bold">{{ $fr['hora'] }}</small>
                        </td>
                        @if($esGlobal)
                            <td><span class="badge bg-dark text-white fw-bold px-2 py-1">{{ $fr['proyecto'] }}</span></td>
                        @endif
                        <td>
                            <strong class="text-dark d-block fw-bold">{{ $fr['cliente'] }}</strong>
                            <small class="text-muted-dark"><i class="fas fa-id-card me-1 text-primary"></i> <strong>{{ $fr['identificacion'] }}</strong></small>
                        </td>
                        <td>
                            <span class="badge-expediente text-mono">{{ $fr['expediente'] }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $fr['tipo'] === 'Total' ? 'badge-metodo-efectivo' : 'badge-metodo-cheque' }}" style="{{ $fr['tipo'] === 'Total' ? 'background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fca5a5 !important;' : 'background-color: #fef3c7 !important; color: #92400e !important; border: 1px solid #fcd34d !important;' }}">
                                {{ $fr['tipo'] }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-danger">{{ $fr['lotes_afectados'] }}</span>
                            @if($fr['lotes_conservados'] && $fr['lotes_conservados'] !== '-')
                                <div class="small text-muted-dark mt-1">Conserva: <span class="text-success fw-bold">{{ $fr['lotes_conservados'] }}</span></div>
                            @endif
                        </td>
                        <td>
                            @if($fr['destino_abonos_raw'] === 'devolucion_efectivo')
                                <span class="badge-metodo" style="background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fca5a5 !important;"><i class="fas fa-hand-holding-usd me-1"></i> Devolución Contable</span>
                            @elseif($fr['destino_abonos_raw'] === 'acreditar_otro_lote')
                                <span class="badge-metodo badge-metodo-efectivo"><i class="fas fa-sync-alt me-1"></i> Acreditado a Contrato</span>
                            @else
                                <span class="badge-metodo badge-metodo-default"><i class="fas fa-ban me-1"></i> Sin Devolución</span>
                            @endif
                        </td>
                        <td class="small text-dark fw-semibold" style="max-width: 220px;">
                            {{ $fr['comentario'] }}
                        </td>
                        <td>
                            <small class="text-muted-dark fw-bold"><i class="fas fa-user-edit me-1 text-secondary"></i> {{ $fr['cajero'] }}</small>
                        </td>
                        <td class="text-end font-weight-bold fs-6 text-mono {{ $fr['monto_devuelto'] > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $fr['monto_devuelto'] > 0 ? '-$' . number_format($fr['monto_devuelto'], 2) : '$0.00' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $esGlobal ? '11' : '10' }}" class="text-center py-3 text-muted">
                            <i class="fas fa-check-circle text-success me-1"></i> No se registraron rescisiones ni desistimientos en este periodo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($filasRescisiones) > 0)
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <td colspan="{{ $esGlobal ? '10' : '9' }}" class="text-end text-uppercase text-secondary">
                            TOTAL OBLIGACIÓN DE DEVOLUCIÓN POR RESCISIÓN ({{ count($filasRescisiones) }} CASOS):
                        </td>
                        <td class="text-end text-danger fs-5 text-mono">
                            -${{ number_format($totalDevolucionesRescisiones, 2) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- LIBRO MAYOR DE AUDITORÍA / DETALLE DE TRANSACCIONES --}}
{{-- ================================================= --}}
<div class="card shadow-sm border-0 mb-5">
    <div class="card-header py-3 bg-white d-flex flex-wrap justify-content-between align-items-center border-bottom gap-2">
        <h5 class="m-0 font-weight-bold text-gray-800">
            <i class="fas fa-list-alt text-primary me-2"></i> Planilla de Detalle de Recaudación ({{ $etiquetaPeriodo }})
        </h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">Mostrar:</label>
                <select id="rf-por-pagina" class="form-select form-select-sm" style="width: 80px;">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="rf-buscador" class="form-control" placeholder="Buscar cliente, recibo, ref, lote...">
            </div>
            <span class="badge-header-success">
                <i class="fas fa-dollar-sign me-1"></i> Total: ${{ number_format($totalRecaudado, 2) }}
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
                    <th>Cliente / Identificación</th>
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
                            <span class="d-block fw-bold text-dark">{{ $fila['fecha'] }}</span>
                            <small class="text-muted-dark text-mono fw-bold">{{ $fila['hora'] }}</small>
                        </td>
                        @if($esGlobal)
                            <td><span class="badge bg-dark text-white fw-bold px-2 py-1">{{ $fila['proyecto'] }}</span></td>
                        @endif
                        <td>
                            <strong class="text-dark d-block fw-bold">{{ $fila['cliente'] }}</strong>
                            <small class="text-muted-dark"><i class="fas fa-id-card me-1 text-primary"></i> <strong>{{ $fila['identificacion'] }}</strong></small>
                        </td>
                        <td>
                            <span class="badge-expediente text-mono">{{ $fila['expediente'] }}</span>
                        </td>
                        <td>
                            <span class="d-block small text-muted-dark">Blq: <strong class="text-dark">{{ $fila['bloques'] }}</strong></span>
                            <span class="fw-bold text-dark">{{ $fila['lotes'] }}</span>
                        </td>
                        <td>
                            @php
                                $tipoStr = strtolower($fila['tipo']);
                                $bConcepto = 'badge-concepto';
                                if (str_contains($tipoStr, 'mensualidad') || str_contains($tipoStr, 'cuota')) {
                                    $bConcepto .= ' badge-concepto-cuota';
                                } elseif (str_contains($tipoStr, 'prima') || str_contains($tipoStr, 'enganche')) {
                                    $bConcepto .= ' badge-concepto-prima';
                                } elseif (str_contains($tipoStr, 'extraordinario') || str_contains($tipoStr, 'reserva')) {
                                    $bConcepto .= ' badge-concepto-extra';
                                }
                            @endphp
                            <span class="{{ $bConcepto }}">{{ $fila['tipo'] }}</span>
                        </td>
                        <td>
                            @php
                                $mStr = strtolower($fila['metodo']);
                                $bMetodo = 'badge-metodo-default';
                                $icono = 'fa-money-bill-wave';
                                if (str_contains($mStr, 'efectivo')) {
                                    $bMetodo = 'badge-metodo-efectivo';
                                    $icono = 'fa-money-bill-wave';
                                } elseif (str_contains($mStr, 'transferencia')) {
                                    $bMetodo = 'badge-metodo-transferencia';
                                    $icono = 'fa-exchange-alt';
                                } elseif (str_contains($mStr, 'depósito') || str_contains($mStr, 'deposito')) {
                                    $bMetodo = 'badge-metodo-deposito';
                                    $icono = 'fa-university';
                                } elseif (str_contains($mStr, 'cheque')) {
                                    $bMetodo = 'badge-metodo-cheque';
                                    $icono = 'fa-money-check';
                                }
                            @endphp
                            <span class="badge-metodo {{ $bMetodo }}">
                                <i class="fas {{ $icono }}"></i> {{ $fila['metodo'] }}
                            </span>
                        </td>
                        <td>
                            <span class="text-mono small text-dark fw-bold">{{ $fila['referencia'] }}</span>
                        </td>
                        <td>
                            <small class="text-muted-dark fw-bold"><i class="fas fa-user-edit me-1 text-secondary"></i> {{ $fila['cajero'] }}</small>
                        </td>
                        <td class="text-end font-weight-bold fs-6 text-mono" style="color: #047857 !important;">
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
    <div id="rf-paginacion" class="card-footer bg-white border-top p-2"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/reportes-paginacion.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Paginación interactiva y búsqueda
    inicializarPaginacionReporte({
        tablaSelector: '#rf-tabla-abonos',
        paginacionSelector: '#rf-paginacion',
        buscadorSelector: '#rf-buscador',
        porPaginaSelector: '#rf-por-pagina',
        porPaginaDefault: 25
    });

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
