@extends('template')

@section('titulo', 'Proyección de Flujo y Recaudación Futura')

@section('contenido')
<style>
    .kpi-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s ease;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-primary font-weight-bold mb-1">
            <i class="fas fa-chart-line me-2"></i> Proyección de Flujo y Recaudación Futura
        </h2>
        <p class="text-muted mb-0">{{ $nombreProyecto }} &middot; Cuotas calendarizadas a {{ $mesesProyeccion }} meses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reportes.proyeccion_flujo.excel', request()->query()) }}" class="btn btn-success shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

{{-- FILTROS --}}
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reportes.proyeccion_flujo') }}" class="row g-3 align-items-end">
            @if($esAdmin)
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Proyecto</label>
                <select name="proyecto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="actual" @selected($filtroSeleccionado === 'actual')>Proyecto Activo</option>
                    <option value="global" @selected($filtroSeleccionado === 'global' || $filtroSeleccionado === 'todos')>🌐 Consolidado Global (Todos)</option>
                    @foreach($proyectosDisponibles as $p)
                        <option value="{{ $p->id }}" @selected($filtroSeleccionado == $p->id)>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Horizonte Rápido</label>
                <select name="meses" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="6" @selected($mesesProyeccion === 6)>Próximos 6 Meses</option>
                    <option value="12" @selected($mesesProyeccion === 12)>Próximos 12 Meses (1 Año)</option>
                    <option value="24" @selected($mesesProyeccion === 24)>Próximos 24 Meses (2 Años)</option>
                    <option value="36" @selected($mesesProyeccion === 36)>Próximos 36 Meses (3 Años)</option>
                    <option value="60" @selected($mesesProyeccion === 60)>Próximos 60 Meses (5 Años)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Mes Desde</label>
                <input type="month" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Mes Hasta</label>
                <input type="month" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    <i class="fas fa-sync-alt me-1"></i> Proyectar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TARJETAS KPI --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card kpi-card bg-primary text-white p-3">
            <span class="text-white-50 small text-uppercase fw-bold">Recaudación Total Proyectada</span>
            <h3 class="font-weight-bold mb-0 mt-1">${{ number_format($totalProyeccion, 2) }}</h3>
            <small class="text-white-50">En el período de {{ $mesesProyeccion }} meses</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card kpi-card bg-success text-white p-3">
            <span class="text-white-50 small text-uppercase fw-bold">Promedio Mensual Esperado</span>
            <h3 class="font-weight-bold mb-0 mt-1">${{ number_format($promedioMensual, 2) }}</h3>
            <small class="text-white-50">Flujo mensual estimado</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card kpi-card bg-info text-white p-3">
            <span class="text-white-50 small text-uppercase fw-bold">Cuotas Programadas</span>
            <h3 class="font-weight-bold mb-0 mt-1">{{ number_format($totalCuotasProyectadas) }}</h3>
            <small class="text-white-50">Cuotas de contratos vigentes</small>
        </div>
    </div>
</div>

{{-- GRÁFICO DE PROYECCIÓN MENSUAL --}}
<div class="card shadow border-0 mb-4">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Curva de Flujo de Efectivo Proyectado Mes a Mes</h6>
    </div>
    <div class="card-body">
        <div style="height: 280px;">
            <canvas id="chartProyeccion"></canvas>
        </div>
    </div>
</div>

{{-- TABLA MES A MES --}}
<div class="card shadow border-0 mb-4">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 font-weight-bold text-primary">Calendario Mensualizado de Ingresos Programados</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Mes / Período</th>
                        <th class="text-center">Cuotas a Cobrar</th>
                        <th class="text-end">Capital Esperado ($)</th>
                        <th class="text-end">Interés Esperado ($)</th>
                        <th class="text-end">Total Esperado ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mesesData as $m)
                        <tr>
                            <td class="fw-bold text-primary">{{ $m['mes_nombre'] }} {{ $m['anio'] }}</td>
                            <td class="text-center fw-bold">{{ $m['cuotas_cantidad'] }}</td>
                            <td class="text-end">${{ number_format($m['capital_esperado'], 2) }}</td>
                            <td class="text-end text-muted">${{ number_format($m['interes_esperado'], 2) }}</td>
                            <td class="text-end font-weight-bold text-success fs-6">${{ number_format($m['total_esperado'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td class="text-uppercase">Total Proyectado:</td>
                        <td class="text-center">{{ $totalCuotasProyectadas }}</td>
                        <td class="text-end">${{ number_format(array_sum(array_column($mesesData, 'capital_esperado')), 2) }}</td>
                        <td class="text-end">${{ number_format(array_sum(array_column($mesesData, 'interes_esperado')), 2) }}</td>
                        <td class="text-end text-success">${{ number_format($totalProyeccion, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/chartM.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartProyeccion');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labelsGrafico) !!},
            datasets: [{
                label: 'Ingresos Programados ($)',
                data: {!! json_encode($dataEsperada) !!},
                backgroundColor: 'rgba(78, 115, 223, 0.75)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) { return '$' + val.toLocaleString(); }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(c) { return ' Esperado: $' + Number(c.raw).toLocaleString(); }
                    }
                }
            }
        }
    });
});
</script>
@endsection
