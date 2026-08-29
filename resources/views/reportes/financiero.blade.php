@extends('template')

@section('titulo', 'Reporte Financiero')

@section('contenido')

<link rel="stylesheet" href="{{ asset('css/reporte_financiero.css') }}">

<div class="rf-page-header">
    <div>
        <h1><i class="fas fa-folder-open me-2 text-primary"></i> Reporte Financiero — Archivos</h1>
        <div class="rf-subtitulo">
            <span class="badge bg-{{ $esGlobal ? 'warning text-dark' : 'primary text-white' }} me-2 px-2 py-1">
                <i class="fas fa-project-diagram me-1"></i> {{ $etiquetaProyecto }}
            </span>
            {{ $etiquetaPeriodo }} &middot; Generado el {{ $generadoEl }}
        </div>
    </div>
    <div class="rf-acciones-exportar d-flex gap-2">
        <a href="{{ route('reportes.financiero.pdf', request()->query()) }}"
           data-rf-exportar data-rf-base="{{ route('reportes.financiero.pdf') }}"
           class="btn btn-danger" target="_blank">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>
        <a href="{{ route('reportes.financiero.excel', request()->query()) }}"
           data-rf-exportar data-rf-base="{{ route('reportes.financiero.excel') }}"
           class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

{{-- ================================================= --}}
{{-- FILTROS --}}
{{-- ================================================= --}}
<div class="rf-filtros">
    <form id="rf-form-filtros" method="GET" action="{{ route('reportes.financiero') }}">
        <div class="row g-3 align-items-end">
            @if(isset($esAdmin) && $esAdmin)
            <div class="col-12 col-md-3">
                <label for="rf-proyecto" class="font-weight-bold text-primary"><i class="fas fa-building me-1"></i> Proyecto / Consolidado</label>
                <select id="rf-proyecto" name="proyecto_id" class="form-select border-primary">
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

            <div class="col-6 col-md-{{ isset($esAdmin) && $esAdmin ? '2' : '3' }}">
                <label for="rf-periodo">Periodo</label>
                <select id="rf-periodo" name="periodo" class="form-select">
                    <option value="hoy" @selected($periodo === 'hoy')>Solo el día actual</option>
                    <option value="dia" @selected($periodo === 'dia')>Día específico</option>
                    <option value="mes" @selected($periodo === 'mes')>Un mes</option>
                    <option value="ytd" @selected($periodo === 'ytd')>Año hasta la fecha actual</option>
                    <option value="anio" @selected($periodo === 'anio')>Año completo</option>
                </select>
            </div>

            <div class="col-6 col-md-2 {{ $periodo === 'dia' ? '' : 'd-none' }}" id="rf-grupo-fecha">
                <label for="rf-fecha">Fecha</label>
                <input type="date" id="rf-fecha" name="fecha" class="form-control" value="{{ $fechaSeleccionada }}" max="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="col-6 col-md-2 {{ in_array($periodo, ['mes', 'anio', 'ytd']) ? '' : 'd-none' }}" id="rf-grupo-anio">
                <label for="rf-anio">Año</label>
                <select id="rf-anio" name="anio" class="form-select">
                    @foreach ($aniosDisponibles as $anioOpcion)
                        <option value="{{ $anioOpcion }}" @selected($anio === $anioOpcion)>{{ $anioOpcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2 {{ $periodo === 'mes' ? '' : 'd-none' }}" id="rf-grupo-mes">
                <label for="rf-mes">Mes</label>
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
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Generar Reporte
                </button>
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
            <p class="rf-kpi-label">Total Ingresos</p>
            <p class="rf-kpi-valor" data-rf-contador="{{ $totalIngresos }}" data-rf-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-money-bill-wave"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Total Gastos</p>
            <p class="rf-kpi-valor" data-rf-contador="{{ $totalGastos }}" data-rf-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-balance-scale"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Balance Neto</p>
            <p class="rf-kpi-valor {{ $balanceNeto >= 0 ? 'rf-positivo' : 'rf-negativo' }}" data-rf-contador="{{ $balanceNeto }}" data-rf-moneda>$0.00</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-users"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Clientes que abonaron</p>
            <p class="rf-kpi-valor" data-rf-contador="{{ $clientesAbonaron }}">{{ $clientesAbonaron }}</p>
        </div>
    </div>
    <div class="rf-kpi">
        <div class="rf-kpi-icono"><i class="fas fa-receipt"></i></div>
        <div class="rf-kpi-texto">
            <p class="rf-kpi-label">Abonos Registrados</p>
            <p class="rf-kpi-valor" data-rf-contador="{{ $cantidadAbonos }}">0</p>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- CUADRO 1: ABONOS / INGRESOS --}}
{{-- ================================================= --}}
<div class="rf-cuadro">
    <div class="rf-cuadro-header rf-header-ingresos">
        <h2><i class="fas fa-coins text-success me-1"></i> Cuadro 1 — Abonos Registrados ({{ $etiquetaPeriodo }})</h2>
        <div class="d-flex align-items-center gap-3">
            <input type="text" id="rf-buscador" class="form-control form-control-sm" style="width: 220px;" placeholder="Buscar cliente, bloque o lote...">
            <span class="rf-badge-total rf-ok">Total: ${{ number_format($totalIngresos, 2) }}</span>
        </div>
    </div>
    <div class="rf-tabla-wrap">
        <table class="rf-tabla" id="rf-tabla-abonos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    @if($esGlobal)
                        <th>Proyecto</th>
                    @endif
                    <th>Cliente</th>
                    <th>Bloque</th>
                    <th>Lote(s)</th>
                    <th>Tipo</th>
                    <th class="rf-num">Abonado</th>
                    <th>Ref.</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filasAbonos as $fila)
                    <tr data-rf-fila>
                        <td>{{ $fila['fecha'] }}</td>
                        <td>{{ $fila['hora'] }}</td>
                        @if($esGlobal)
                            <td><span class="badge bg-secondary text-white">{{ $fila['proyecto'] }}</span></td>
                        @endif
                        <td>{{ $fila['cliente'] }} <span class="rf-pv-badge">{{ $fila['pv'] }}</span></td>
                        <td>{{ $fila['bloques'] }}</td>
                        <td>{{ $fila['lotes'] }}</td>
                        <td>{{ $fila['tipo'] }}</td>
                        <td class="rf-num rf-monto-ingreso">${{ number_format($fila['monto'], 2) }}</td>
                        <td>{{ $fila['referencia'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $esGlobal ? '9' : '8' }}" class="rf-vacio">No se registraron abonos en el periodo seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ================================================= --}}
{{-- CUADRO 2: SALIDAS / GASTOS --}}
{{-- ================================================= --}}
<div class="rf-cuadro">
    <div class="rf-cuadro-header rf-header-gastos">
        <h2><i class="fas fa-arrow-circle-down text-danger me-1"></i> Cuadro 2 — Salidas / Gastos ({{ $etiquetaPeriodo }})</h2>
        <span class="rf-badge-total rf-bad">Total: ${{ number_format($totalGastos, 2) }}</span>
    </div>
    <div class="rf-tabla-wrap">
        <table class="rf-tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    @if($esGlobal)
                        <th>Proyecto</th>
                    @endif
                    <th>Descripción</th>
                    <th class="rf-num">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filasSalidas as $fila)
                    <tr>
                        <td>{{ $fila['fecha'] }}</td>
                        <td>{{ $fila['hora'] }}</td>
                        @if($esGlobal)
                            <td><span class="badge bg-secondary text-white">{{ $fila['proyecto'] }}</span></td>
                        @endif
                        <td>{{ $fila['descripcion'] }}</td>
                        <td class="rf-num rf-monto-gasto">${{ number_format($fila['monto'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $esGlobal ? '5' : '4' }}" class="rf-vacio">No se registraron salidas en el periodo seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/reporte_financiero.js') }}"></script>
@endsection
