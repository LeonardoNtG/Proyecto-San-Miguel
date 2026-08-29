@extends('template')

@section('titulo', 'Gráficos y Estadísticas')

@section('contenido')

<link rel="stylesheet" href="{{ asset('css/reporte_financiero.css') }}">
<link rel="stylesheet" href="{{ asset('css/graficos.css') }}">

<div class="rf-page-header">
    <div>
        <h1><i class="fas fa-chart-line me-2 text-primary"></i> Gráficos y Estadísticas</h1>
        <div class="rf-subtitulo">
            <span class="badge bg-{{ $esGlobal ? 'warning text-dark' : 'primary text-white' }} me-2 px-2 py-1">
                <i class="fas fa-project-diagram me-1"></i> {{ $etiquetaProyecto }}
            </span>
            {{ $etiquetaGrupo }} &middot; Generado el {{ $generadoEl }}
        </div>
    </div>
    <div class="rf-acciones-exportar d-flex gap-2">
        <button type="button" id="gr-btn-imprimir" class="btn btn-danger">
            <i class="fas fa-file-pdf me-1"></i> Imprimir PDF
        </button>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILTROS --}}
{{-- ================================================= --}}
<div class="rf-filtros">
    <form id="gr-form-filtros" method="GET" action="{{ route('dashboard.grafico') }}">
        <div class="row g-3 align-items-end">
            @if(isset($esAdmin) && $esAdmin)
            <div class="col-12 col-md-3">
                <label for="gr-proyecto" class="font-weight-bold text-primary"><i class="fas fa-building me-1"></i> Proyecto / Consolidado</label>
                <select id="gr-proyecto" name="proyecto_id" class="form-select border-primary" onchange="document.getElementById('gr-form-filtros').submit();">
                    <option value="actual" @selected($proyectoFiltro === 'actual')>Proyecto Actual ({{ $userLotificaciones->firstWhere('id', session('lotificacion_id'))->nombre ?? 'Activo' }})</option>
                    <option value="global" @selected($proyectoFiltro === 'global' || $proyectoFiltro === 'todos')>⭐ CONSOLIDADO GLOBAL (TODAS)</option>
                    <optgroup label="Filtrar por Proyecto Específico">
                        @foreach ($proyectosDisponibles as $proy)
                            <option value="{{ $proy->id }}" @selected((string)$proyectoFiltro === (string)$proy->id)>{{ $proy->nombre }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            @endif

            <div class="col-6 col-md-{{ isset($esAdmin) && $esAdmin ? '3' : '3' }}">
                <label for="gr-agrupacion">Agrupar por</label>
                <select id="gr-agrupacion" name="agrupacion" class="form-select">
                    <option value="dia" @selected($agrupacion === 'dia')>Día</option>
                    <option value="mes" @selected($agrupacion === 'mes')>Mes</option>
                    <option value="anio" @selected($agrupacion === 'anio')>Año (histórico)</option>
                </select>
            </div>

            <div class="col-6 col-md-3 {{ $agrupacion === 'anio' ? 'd-none' : '' }}" id="gr-grupo-anio">
                <label for="gr-anio">Año</label>
                <select id="gr-anio" name="anio" class="form-select">
                    @foreach ($aniosDisponibles as $anioOpcion)
                        <option value="{{ $anioOpcion }}" @selected($anio === $anioOpcion)>{{ $anioOpcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-3 {{ $agrupacion === 'dia' ? '' : 'd-none' }}" id="gr-grupo-mes">
                <label for="gr-mes">Mes</label>
                <select id="gr-mes" name="mes" class="form-select">
                    @foreach ($nombresMeses as $numero => $nombre)
                        <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- ================================================= --}}
{{-- KPIs --}}
{{-- ================================================= --}}
<div class="rf-kpis">
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Ingresos ({{ $etiquetaGrupo }})</p>
            <p class="rf-kpi-valor" data-gr-contador="{{ $totalIngresos }}" data-gr-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-money-bill-wave"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Gastos ({{ $etiquetaGrupo }})</p>
            <p class="rf-kpi-valor" data-gr-contador="{{ $totalGastos }}" data-gr-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-balance-scale"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Balance Neto</p>
            <p class="rf-kpi-valor" data-gr-contador="{{ $balanceNeto }}" data-gr-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-users"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Total Clientes</p>
            <p class="rf-kpi-valor" data-gr-contador="{{ $totalClientes }}">0</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-file-signature"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Total Contratos</p>
            <p class="rf-kpi-valor" data-gr-contador="{{ $totalContratos }}">0</p>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILA 1: Ingresos vs Gastos + Distribución de Contratos --}}
{{-- ================================================= --}}
<div class="gr-grid">
    <div class="gr-card">
        <div class="gr-card-header">
            <h2><i class="fas fa-chart-bar text-primary me-1"></i> Ingresos vs Gastos</h2>
            <span class="gr-total {{ $balanceNeto >= 0 ? 'gr-ok' : 'gr-bad' }}">Balance: ${{ number_format($balanceNeto, 2) }}</span>
        </div>
        <div class="gr-canvas-wrap gr-tall">
            <canvas id="grChartComparativo"></canvas>
        </div>
    </div>

    <div class="gr-card">
        <div class="gr-card-header">
            <h2><i class="fas fa-file-contract text-info me-1"></i> Contratos — Distribución Actual</h2>
        </div>
        <div class="gr-canvas-wrap gr-donut">
            <canvas id="grChartDistribucion"></canvas>
        </div>
        <div class="gr-estado-leyenda">
            <div>
                <span class="gr-valor">{{ $totalVigentes }}</span>
                <span class="gr-etiqueta">Vigentes</span>
            </div>
            <div>
                <span class="gr-valor">{{ $totalFinalizados }}</span>
                <span class="gr-etiqueta">Finalizados</span>
            </div>
            <div>
                <span class="gr-valor">{{ $totalRescindidos }}</span>
                <span class="gr-etiqueta">Rescindidos</span>
            </div>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILA 2: Ingresos y Gastos por separado --}}
{{-- ================================================= --}}
<div class="gr-grid-2">
    <div class="gr-card">
        <div class="gr-card-header">
            <h2><i class="fas fa-arrow-trend-up text-success me-1"></i> Ingresos</h2>
            <span class="gr-total gr-ok">Total: ${{ number_format($totalIngresos, 2) }}</span>
        </div>
        <div class="gr-canvas-wrap">
            <canvas id="grChartIngresos"></canvas>
        </div>
    </div>

    <div class="gr-card">
        <div class="gr-card-header">
            <h2><i class="fas fa-arrow-trend-down text-danger me-1"></i> Gastos</h2>
            <span class="gr-total gr-bad">Total: ${{ number_format($totalGastos, 2) }}</span>
        </div>
        <div class="gr-canvas-wrap">
            <canvas id="grChartGastos"></canvas>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILA 3: Contratos por Estado, en el tiempo --}}
{{-- ================================================= --}}
<div class="gr-card mb-4">
    <div class="gr-card-header">
        <h2><i class="fas fa-people-group text-primary me-1"></i> Contratos Nuevos por Estado ({{ $etiquetaGrupo }})</h2>
        <span class="gr-total gr-neutro">Total Contratos: {{ $totalContratos }}</span>
    </div>
    <div class="gr-canvas-wrap gr-tall">
        <canvas id="grChartContratos"></canvas>
    </div>
</div>

@endsection

@php
    $chartPayload = [
        'labels' => $labels,
        'dataIngresos' => $dataIngresos,
        'dataGastos' => $dataGastos,
        'dataBalance' => $dataBalance,
        'dataVigente' => $dataVigente,
        'dataFinalizado' => $dataFinalizado,
        'dataRescindido' => $dataRescindido,
        'totalVigentes' => $totalVigentes,
        'totalFinalizados' => $totalFinalizados,
        'totalRescindidos' => $totalRescindidos,
    ];
@endphp

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.graficosData = @json($chartPayload);
</script>
<script src="{{ asset('js/graficos_dashboard.js') }}"></script>
@endsection
