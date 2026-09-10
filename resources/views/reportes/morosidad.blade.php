@extends('template')

@section('titulo', 'Reporte de Morosidad y Antigüedad de Saldos')

@section('contenido')
<style>
    .kpi-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
    }
    .bg-orange { background-color: #f97316; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-danger font-weight-bold mb-1">
            <i class="fas fa-exclamation-circle me-2"></i> Reporte de Morosidad y Antigüedad de Saldos
        </h2>
        <p class="text-muted mb-0">{{ $nombreProyecto }} &middot; Actualizado al {{ $generadoEl }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reportes.morosidad.pdf', request()->query()) }}" class="btn btn-danger shadow-sm">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>
        <a href="{{ route('reportes.morosidad.excel', request()->query()) }}" class="btn btn-success shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

{{-- FILTROS --}}
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reportes.morosidad') }}" class="row g-3 align-items-end">
            @if($esAdmin)
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Proyecto</label>
                <select name="proyecto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="actual" @selected($filtroSeleccionado === 'actual')>Proyecto Activo</option>
                    <option value="global" @selected($filtroSeleccionado === 'global' || $filtroSeleccionado === 'todos')>🌐 Consolidado Global</option>
                    @foreach($proyectosDisponibles as $p)
                        <option value="{{ $p->id }}" @selected($filtroSeleccionado == $p->id)>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Antigüedad de Atraso</label>
                <select name="rango" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="todos" @selected($rangoFiltro === 'todos')>Todos los Rangos</option>
                    <option value="1_30" @selected($rangoFiltro === '1_30')>1 a 30 días ({{ $resumenBuckets['1_30']['count'] }})</option>
                    <option value="31_60" @selected($rangoFiltro === '31_60')>31 a 60 días ({{ $resumenBuckets['31_60']['count'] }})</option>
                    <option value="61_90" @selected($rangoFiltro === '61_90')>61 a 90 días ({{ $resumenBuckets['61_90']['count'] }})</option>
                    <option value="mas_90" @selected($rangoFiltro === 'mas_90')>+90 días - Crítico ({{ $resumenBuckets['mas_90']['count'] }})</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Vencimiento Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Vencimiento Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TARJETAS KPI DE MOROSIDAD --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-info text-white p-3">
            <span class="text-white-50 small text-uppercase fw-bold">1 a 30 Días</span>
            <h3 class="font-weight-bold mb-0 mt-1">{{ $resumenBuckets['1_30']['count'] }} clientes</h3>
            <small class="text-white-50">${{ number_format($resumenBuckets['1_30']['total'], 2) }} exigible</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-warning text-dark p-3">
            <span class="text-dark small text-uppercase fw-bold opacity-75">31 a 60 Días</span>
            <h3 class="font-weight-bold mb-0 mt-1">{{ $resumenBuckets['31_60']['count'] }} clientes</h3>
            <small class="opacity-75">${{ number_format($resumenBuckets['31_60']['total'], 2) }} exigible</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card text-white p-3" style="background-color: #f97316;">
            <span class="text-white-50 small text-uppercase fw-bold">61 a 90 Días</span>
            <h3 class="font-weight-bold mb-0 mt-1">{{ $resumenBuckets['61_90']['count'] }} clientes</h3>
            <small class="text-white-50">${{ number_format($resumenBuckets['61_90']['total'], 2) }} exigible</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-danger text-white p-3">
            <span class="text-white-50 small text-uppercase fw-bold">+90 Días (Crítico)</span>
            <h3 class="font-weight-bold mb-0 mt-1">{{ $resumenBuckets['mas_90']['count'] }} clientes</h3>
            <small class="text-white-50">${{ number_format($resumenBuckets['mas_90']['total'], 2) }} exigible</small>
        </div>
    </div>
</div>

{{-- TABLA DE DETALLE --}}
<div class="card shadow border-0 mb-4">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-danger">Listado de Clientes con Cuotas Vencidas</h6>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">Mostrar:</label>
                <select id="mora-por-pagina" class="form-select form-select-sm" style="width: 80px;">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="mora-buscador" class="form-control" placeholder="Buscar cliente, lote...">
            </div>
            <span class="badge bg-danger">Deuda Exigible: ${{ number_format($totalDeudaExigible, 2) }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="mora-tabla">
                <thead class="table-light">
                    <tr>
                        <th>Expediente</th>
                        <th>Cliente / Contacto</th>
                        <th>Lote</th>
                        <th class="text-center">Cuotas Vencidas</th>
                        <th class="text-end">Cuotas Vencidas ($)</th>
                        <th class="text-end">Mora Acumulada ($)</th>
                        <th class="text-end">Total Exigible ($)</th>
                        <th class="text-center">Días de Atraso</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filas as $f)
                        <tr>
                            <td class="fw-bold text-danger">{{ $f['expediente'] }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $f['cliente_nombre'] }}</div>
                                <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $f['telefono'] }} &middot; <i class="fas fa-id-card me-1"></i>{{ $f['identificacion'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $f['proyecto'] }}</span>
                                <div class="fw-bold text-primary small">{{ $f['lote_codigo'] }}</div>
                            </td>
                            <td class="text-center fw-bold">
                                <span class="badge bg-danger rounded-pill">{{ $f['cuotas_vencidas_count'] }}</span>
                            </td>
                            <td class="text-end font-weight-bold">${{ number_format($f['monto_cuotas_vencidas'], 2) }}</td>
                            <td class="text-end text-warning font-weight-bold">${{ number_format($f['mora_acumulada'], 2) }}</td>
                            <td class="text-end text-danger font-weight-bold fs-6">${{ number_format($f['total_deuda_vencida'], 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $f['badge_class'] }} px-2 py-1">
                                    {{ $f['max_dias_retraso'] }} días
                                </span>
                            </td>
                            <td class="text-center">
                                @if($f['cliente_id'])
                                    <a href="{{ route('registro.show', $f['cliente_id']) }}" class="btn btn-sm btn-outline-danger" title="Ver Expediente">
                                        <i class="fas fa-folder-open"></i> Cobrar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="fila-vacia">
                            <td colspan="9" class="text-center py-5 text-success">
                                <i class="fas fa-check-circle fa-3x mb-3 d-block opacity-75"></i>
                                ¡Excelente! No hay clientes con cuotas en mora para este criterio.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td colspan="4" class="text-uppercase">Totales de Cartera Vencida:</td>
                        <td class="text-end">${{ number_format($totalCapitalVencido, 2) }}</td>
                        <td class="text-end text-warning">${{ number_format($totalMoraAcumulada, 2) }}</td>
                        <td class="text-end text-danger">${{ number_format($totalDeudaExigible, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="mora-paginacion" class="card-footer bg-white border-top p-2"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/reportes-paginacion.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    inicializarPaginacionReporte({
        tablaSelector: '#mora-tabla',
        paginacionSelector: '#mora-paginacion',
        buscadorSelector: '#mora-buscador',
        porPaginaSelector: '#mora-por-pagina',
        porPaginaDefault: 25
    });
});
</script>
@endsection
