@extends('template')

@section('titulo', 'Reporte de Inventario de Lotes')

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
    .badge-lote-disp { background-color: #10b981; color: white; }
    .badge-lote-res { background-color: #f59e0b; color: white; }
    .badge-lote-ven { background-color: #ef4444; color: white; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-primary font-weight-bold mb-1">
            <i class="fas fa-boxes me-2"></i> Inventario y Disponibilidad de Lotes
        </h2>
        <p class="text-muted mb-0">{{ $nombreProyecto }} &middot; Actualizado al {{ $generadoEl }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reportes.inventario_lotes.pdf', request()->query()) }}" class="btn btn-danger shadow-sm">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>
        <a href="{{ route('reportes.inventario_lotes.excel', request()->query()) }}" class="btn btn-success shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

{{-- FILTROS --}}
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reportes.inventario_lotes') }}" class="row g-3 align-items-end">
            @if($esAdmin)
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Proyecto</label>
                <select name="proyecto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="actual" @selected($filtroSeleccionado === 'actual')>Proyecto Activo</option>
                    <option value="global" @selected($filtroSeleccionado === 'global' || $filtroSeleccionado === 'todos')>🌐 Global (Todos)</option>
                    @foreach($proyectosDisponibles as $p)
                        <option value="{{ $p->id }}" @selected($filtroSeleccionado == $p->id)>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Bloque</label>
                <select name="bloque_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="todos" @selected($bloqueFiltro === 'todos')>Todos los Bloques</option>
                    @foreach($bloquesDisponibles as $b)
                        <option value="{{ $b->id_bloque }}" @selected($bloqueFiltro == $b->id_bloque)>Bloque {{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Estado de Lote</label>
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="todos" @selected($estadoFiltro === 'todos')>Todos los Estados</option>
                    <option value="Disponible" @selected($estadoFiltro === 'Disponible')>Disponibles</option>
                    <option value="Reservado" @selected($estadoFiltro === 'Reservado')>Reservados</option>
                    <option value="Vendido" @selected($estadoFiltro === 'Vendido')>Vendidos</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Registro Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Registro Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TARJETAS KPI --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Lotes</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalLotes) }}</h3>
                    <small class="text-white-50">Valor: ${{ number_format($valorTotal, 2) }}</small>
                </div>
                <i class="fas fa-th-large fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Disponibles</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalDisponibles) }}</h3>
                    <small class="text-white-50">Valor: ${{ number_format($valorDisponible, 2) }}</small>
                </div>
                <i class="fas fa-check-circle fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-warning text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-dark small text-uppercase fw-bold opacity-75">Reservados</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalReservados) }}</h3>
                    <small class="opacity-75">Valor: ${{ number_format($valorReservado, 2) }}</small>
                </div>
                <i class="fas fa-bookmark fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-danger text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Vendidos ({{ $porcentajeOcupacion }}%)</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalVendidos) }}</h3>
                    <small class="text-white-50">Valor: ${{ number_format($valorVendido, 2) }}</small>
                </div>
                <i class="fas fa-handshake fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

{{-- TABLA DE DETALLE --}}
<div class="card shadow border-0 mb-4">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-primary">Detalle Físico y Financiero del Inventario</h6>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">Mostrar:</label>
                <select id="inv-por-pagina" class="form-select form-select-sm" style="width: 80px;">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="inv-buscador" class="form-control" placeholder="Buscar lote, bloque...">
            </div>
            <span class="badge bg-secondary">{{ $lotes->count() }} lotes</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="inv-tabla">
                <thead class="table-light">
                    <tr>
                        @if($esGlobal)
                            <th>Proyecto</th>
                        @endif
                        <th>Bloque</th>
                        <th>N° Lote</th>
                        <th class="text-end">Área (m²)</th>
                        <th class="text-end">Área (vrs²)</th>
                        <th class="text-end">Precio Base ($)</th>
                        <th class="text-center">Estado</th>
                        <th>Cliente / Titular</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lotes as $lote)
                        @php
                            $vrs2 = $lote->area_metros / 0.705;
                            $ventaActiva = $lote->ventas->first();
                            $clienteTitular = $ventaActiva && $ventaActiva->cliente ? $ventaActiva->cliente->nombres_apellidos : '-';
                        @endphp
                        <tr>
                            @if($esGlobal)
                                <td>
                                    <span class="badge bg-dark">{{ $lote->bloque && $lote->bloque->lotificacion ? $lote->bloque->lotificacion->nombre : 'N/A' }}</span>
                                </td>
                            @endif
                            <td class="fw-bold text-primary">Bloque {{ $lote->bloque ? $lote->bloque->nombre : '-' }}</td>
                            <td class="fw-bold">{{ $lote->numero_lote }}</td>
                            <td class="text-end">{{ number_format($lote->area_metros, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($vrs2, 2) }}</td>
                            <td class="text-end font-weight-bold text-success">${{ number_format($lote->precio_base, 2) }}</td>
                            <td class="text-center">
                                @if($lote->estado === 'Disponible')
                                    <span class="badge badge-lote-disp px-3 py-1">Disponible</span>
                                @elseif($lote->estado === 'Reservado')
                                    <span class="badge badge-lote-res px-3 py-1">Reservado</span>
                                @else
                                    <span class="badge badge-lote-ven px-3 py-1">Vendido</span>
                                @endif
                            </td>
                            <td>
                                @if($clienteTitular !== '-')
                                    <i class="fas fa-user-check text-muted me-1"></i> <span class="fw-bold">{{ $clienteTitular }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="fila-vacia">
                            <td colspan="{{ $esGlobal ? '8' : '7' }}" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 d-block opacity-50"></i>
                                No se encontraron lotes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td colspan="{{ $esGlobal ? '3' : '2' }}" class="text-uppercase">Totales:</td>
                        <td class="text-end">{{ number_format($areaM2Total, 2) }} m²</td>
                        <td class="text-end">{{ number_format($areaVrsTotal, 2) }} vrs²</td>
                        <td class="text-end text-success">${{ number_format($valorTotal, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="inv-paginacion" class="card-footer bg-white border-top p-2"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/reportes-paginacion.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    inicializarPaginacionReporte({
        tablaSelector: '#inv-tabla',
        paginacionSelector: '#inv-paginacion',
        buscadorSelector: '#inv-buscador',
        porPaginaSelector: '#inv-por-pagina',
        porPaginaDefault: 25
    });
});
</script>
@endsection
