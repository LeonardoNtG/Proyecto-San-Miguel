@extends('template')

@section('titulo', 'Reporte de Cartera de Clientes y Abonos')

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
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-primary font-weight-bold mb-1">
            <i class="fas fa-users-cog me-2"></i> Cartera de Clientes, Contratos y Abonos
        </h2>
        <p class="text-muted mb-0">{{ $nombreProyecto }} &middot; Actualizado al {{ $generadoEl }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reportes.cartera_clientes.pdf', request()->query()) }}" class="btn btn-danger shadow-sm">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>
        <a href="{{ route('reportes.cartera_clientes.excel', request()->query()) }}" class="btn btn-success shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

{{-- FILTROS --}}
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reportes.cartera_clientes') }}" class="row g-3 align-items-end">
            @if($esAdmin)
            <div class="col-md-2">
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
                <label class="form-label small fw-bold text-muted mb-1">Estado Contrato</label>
                <select name="estado_contrato" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="todos" @selected($estadoContrato === 'todos')>Todos</option>
                    <option value="Vigente" @selected($estadoContrato === 'Vigente')>Vigentes</option>
                    <option value="Finalizado" @selected($estadoContrato === 'Finalizado')>Cancelados</option>
                    <option value="Rescindido" @selected($estadoContrato === 'Rescindido')>Rescindidos</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Venta Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Venta Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Buscar Cliente / Exp.</label>
                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Nombre, cédula, exp..." value="{{ $busqueda }}">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    <i class="fas fa-search"></i>
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
                    <span class="text-white-50 small text-uppercase fw-bold">Contratos Listados</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalContratos) }}</h3>
                    <small class="text-white-50">Venta: ${{ number_format($totalPrecioVentas, 2) }}</small>
                </div>
                <i class="fas fa-file-contract fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Recaudado</span>
                    <h3 class="font-weight-bold mb-0 mt-1">${{ number_format($totalAbonadoGeneral, 2) }}</h3>
                    <small class="text-white-50">Primas + Cuotas abonadas</small>
                </div>
                <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-info text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Saldo por Cobrar</span>
                    <h3 class="font-weight-bold mb-0 mt-1">${{ number_format($totalSaldoGeneral, 2) }}</h3>
                    <small class="text-white-50">Capital pendiente de cobro</small>
                </div>
                <i class="fas fa-balance-scale-right fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card bg-danger text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Clientes con Mora</span>
                    <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalClientesConMora) }}</h3>
                    <small class="text-white-50">Mora total: ${{ number_format($totalMoraGeneral, 2) }}</small>
                </div>
                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

{{-- TABLA DE DETALLE --}}
<div class="card shadow border-0 mb-4">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-primary">Detalle de Cartera y Amortización por Cliente</h6>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">Mostrar:</label>
                <select id="cartera-por-pagina" class="form-select form-select-sm" style="width: 80px;">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="cartera-buscador" class="form-control" placeholder="Buscar cliente, exp, lote...">
            </div>
            <span class="badge bg-secondary">{{ count($filas) }} clientes</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="cartera-tabla">
                <thead class="table-light">
                    <tr>
                        <th>Expediente</th>
                        <th>Cliente / Titular</th>
                        <th>Lote Asignado</th>
                        <th class="text-end">Precio Venta ($)</th>
                        <th class="text-end">Total Abonado ($)</th>
                        <th class="text-end">Saldo Capital ($)</th>
                        <th class="text-center">Avance Cuotas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filas as $f)
                        <tr>
                            <td class="fw-bold text-primary">{{ $f['expediente'] }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $f['cliente_nombre'] }}</div>
                                <small class="text-muted"><i class="fas fa-id-card me-1"></i>{{ $f['identificacion'] }} &middot; <i class="fas fa-phone me-1"></i>{{ $f['telefono'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $f['proyecto'] }}</span>
                                <div class="fw-bold text-primary small">{{ $f['lote_codigo'] }}</div>
                            </td>
                            <td class="text-end font-weight-bold">${{ number_format($f['precio_venta'], 2) }}</td>
                            <td class="text-end text-success font-weight-bold">${{ number_format($f['total_abonado'], 2) }}</td>
                            <td class="text-end text-danger font-weight-bold">${{ number_format($f['saldo_restante'], 2) }}</td>
                            <td class="text-center">
                                <div class="small fw-bold">{{ $f['cuotas_pagadas'] }} / {{ $f['cuotas_totales'] }}</div>
                                <div class="progress" style="height: 5px; width: 80px; margin: 0 auto;">
                                    @php $porc = $f['cuotas_totales'] > 0 ? ($f['cuotas_pagadas'] / $f['cuotas_totales']) * 100 : 0; @endphp
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $porc }}%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($f['cuotas_mora'] > 0)
                                    <span class="badge bg-danger px-2 py-1"><i class="fas fa-clock me-1"></i> {{ $f['estado_cliente'] }}</span>
                                    <div class="small text-danger fw-bold">+${{ number_format($f['mora_pendiente'], 2) }} mora</div>
                                @elseif($f['estado_contrato'] === 'Finalizado')
                                    <span class="badge bg-success px-2 py-1"><i class="fas fa-check-double me-1"></i> Cancelado</span>
                                @elseif($f['estado_contrato'] === 'Rescindido')
                                    <span class="badge bg-dark px-2 py-1"><i class="fas fa-ban me-1"></i> Rescindido</span>
                                @else
                                    <span class="badge bg-success px-2 py-1"><i class="fas fa-check me-1"></i> Al Día</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($f['cliente_id'])
                                    <a href="{{ route('registro.show', $f['cliente_id']) }}" class="btn btn-sm btn-outline-primary" title="Ver Expediente">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="fila-vacia">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-user-slash fa-3x mb-3 d-block opacity-50"></i>
                                No se encontraron contratos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td colspan="3" class="text-uppercase">Totales Generales:</td>
                        <td class="text-end">${{ number_format($totalPrecioVentas, 2) }}</td>
                        <td class="text-end text-success">${{ number_format($totalAbonadoGeneral, 2) }}</td>
                        <td class="text-end text-danger">${{ number_format($totalSaldoGeneral, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="cartera-paginacion" class="card-footer bg-white border-top p-2"></div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/reportes-paginacion.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    inicializarPaginacionReporte({
        tablaSelector: '#cartera-tabla',
        paginacionSelector: '#cartera-paginacion',
        buscadorSelector: '#cartera-buscador',
        porPaginaSelector: '#cartera-por-pagina',
        porPaginaDefault: 25
    });
});
</script>
@endsection
