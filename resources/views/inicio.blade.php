@extends('template')

@section('titulo', 'Inicio - Panel de Control')

@section('contenido')
<style>
    .dash-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        border-radius: 16px;
        color: #ffffff;
        padding: 24px 28px;
        box-shadow: 0 10px 25px rgba(30, 58, 138, 0.15);
        margin-bottom: 24px;
    }
    .kpi-card-clean {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #edf2f7;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }
    .kpi-card-clean:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.07);
    }
    .kpi-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .quick-action-btn {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        color: #334155;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .quick-action-btn:hover {
        background: #f8fafc;
        border-color: #3b82f6;
        color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }
    .badge-caja-activa {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #ffffff;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
    }
</style>

{{-- 1. CABECERA EJECUTIVA --}}
<div class="dash-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-white text-primary fw-bold px-2 py-1">
                <i class="fas fa-building me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
            </span>
            <small class="opacity-75">{{ now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</small>
        </div>
        <h2 class="fw-bold mb-0">¡Bienvenido, {{ auth()->user()->name }}!</h2>
        <p class="mb-0 opacity-90 small">Resumen de operaciones y estado comercial del proyecto.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if($cajaAbierta)
            <div class="badge-caja-activa">
                <i class="fas fa-cash-register me-1 text-success"></i> Caja Abierta: <strong>${{ number_format($saldoCajaActual, 2) }}</strong>
            </div>
            <a href="{{ route('reportes.cierre_caja') }}" class="btn btn-light btn-sm fw-bold text-dark px-3">
                <i class="fas fa-receipt me-1"></i> Arqueo
            </a>
        @else
            <div class="badge-caja-activa bg-warning text-dark border-0">
                <i class="fas fa-lock me-1"></i> Caja Cerrada
            </div>
            <a href="{{ route('reportes.index') }}" class="btn btn-light btn-sm fw-bold text-dark px-3">
                <i class="fas fa-key me-1"></i> Abrir Caja
            </a>
        @endif
    </div>
</div>

{{-- 2. ACCESOS DIRECTOS DE 1 CLIC (COMPACTOS Y MODERNOS) --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('registro.create') }}" class="quick-action-btn">
            <div class="text-primary fs-5"><i class="fas fa-plus-circle"></i></div>
            <span>Nueva Venta</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('registro.index') }}" class="quick-action-btn">
            <div class="text-success fs-5"><i class="fas fa-hand-holding-usd"></i></div>
            <span>Cobrar Abono</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('estados_cuenta') }}" class="quick-action-btn">
            <div class="text-info fs-5"><i class="fas fa-file-invoice"></i></div>
            <span>Estados de Cuenta</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('reportes.financiero') }}" class="quick-action-btn">
            <div class="text-warning fs-5"><i class="fas fa-chart-pie"></i></div>
            <span>Reportes Financieros</span>
        </a>
    </div>
</div>

{{-- 3. TARJETAS KPI (4 MÉTRICAS ESENCIALES) --}}
<div class="row g-3 mb-4">
    {{-- KPI 1: Recaudación Mes --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-clean">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Recaudación ({{ $mesActualNombre }})</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">${{ number_format($recaudacionMes, 2) }}</h3>
                    <small class="text-success fw-bold"><i class="fas fa-arrow-up me-1"></i>Ingresos confirmados</small>
                </div>
                <div class="kpi-icon-box bg-success-subtle text-success" style="background-color: #dcfce7; color: #16a34a;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI 2: Inventario de Lotes --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-clean">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Ocupación / Ventas</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">{{ $porcentajeOcupacion }}%</h3>
                    <small class="text-muted"><strong>{{ $lotesVendidos }}</strong> de {{ $totalLotes }} lotes vendidos</small>
                </div>
                <div class="kpi-icon-box" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI 3: Contratos Vigentes --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-clean">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Contratos Activos</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">{{ $totalContratosVigentes }}</h3>
                    <small class="text-muted"><i class="fas fa-user-check me-1 text-primary"></i>Clientes amortizando</small>
                </div>
                <div class="kpi-icon-box" style="background-color: #f3e8ff; color: #9333ea;">
                    <i class="fas fa-file-contract"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI 4: Cartera en Mora --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-clean">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Mora Exigible</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1">${{ number_format($montoMoraExigible, 2) }}</h3>
                    <small class="text-danger fw-bold">
                        @if($clientesConMora > 0)
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $clientesConMora }} cliente(s) en atraso
                        @else
                            <i class="fas fa-check-circle me-1 text-success"></i>Cartera al día
                        @endif
                    </small>
                </div>
                <div class="kpi-icon-box" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. GRÁFICOS RESUMEN (EQUILIBRADOS Y LIMPIOS) --}}
<div class="row g-4 mb-4">
    {{-- Gráfico de Ingresos --}}
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-chart-area text-primary me-2"></i> Tendencia de Recaudación (Últimos 6 Meses)
                </h6>
                <a href="{{ route('reportes.proyeccion_flujo') }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-bold small">
                    Ver Proyecciones <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div style="height: 240px;">
                    <canvas id="chartIngresosDashboard"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Distribución de Lotes --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
            <div class="card-header py-3 bg-white border-bottom">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-chart-pie text-info me-2"></i> Estado del Inventario
                </h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 180px; position: relative;">
                    <canvas id="chartLotesDashboard"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                    <div>
                        <span class="d-block small text-muted">Disponibles</span>
                        <strong class="text-success fs-6">{{ $lotesDisponibles }}</strong>
                    </div>
                    <div>
                        <span class="d-block small text-muted">Reservados</span>
                        <strong class="text-warning fs-6">{{ $lotesReservados }}</strong>
                    </div>
                    <div>
                        <span class="d-block small text-muted">Vendidos</span>
                        <strong class="text-primary fs-6">{{ $lotesVendidos }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 5. TABLAS DE GESTIÓN RÁPIDA (ÚLTIMOS ABONOS Y PRÓXIMOS COBROS) --}}
<div class="row g-4 mb-4">
    {{-- Últimos Abonos --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-receipt text-success me-2"></i> Últimos Abonos Cobrados
                </h6>
                <a href="{{ route('reportes.financiero') }}" class="small text-muted text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Recibo</th>
                                <th>Cliente</th>
                                <th>Lote</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosAbonos as $abono)
                                @php
                                    $loteCod = $abono->venta && $abono->venta->lotes->first() 
                                        ? ($abono->venta->lotes->first()->bloque ? 'Blq '.$abono->venta->lotes->first()->bloque->nombre.' - '.$abono->venta->lotes->first()->numero_lote : $abono->venta->lotes->first()->numero_lote) 
                                        : '-';
                                @endphp
                                <tr>
                                    <td class="fw-bold text-primary small">#{{ $abono->numero_recibo_formateado }}</td>
                                    <td>
                                        <div class="fw-bold text-dark small">{{ $abono->venta && $abono->venta->cliente ? $abono->venta->cliente->nombres_apellidos : 'Cliente Desconocido' }}</div>
                                        <small class="text-muted">{{ $abono->fecha_pago ? \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') : '-' }} &middot; {{ $abono->metodo_pago }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $loteCod }}</span></td>
                                    <td class="text-end fw-bold text-success">${{ number_format($abono->monto_abonado, 2) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('imprimirRecibo', $abono->id_abono) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Imprimir Recibo">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">
                                        No hay cobros registrados recientemente en este proyecto.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Próximos Vencimientos a Cobrar --}}
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-calendar-day text-primary me-2"></i> Cobros Próximos (7 Días)
                </h6>
                <a href="{{ route('reportes.morosidad') }}" class="small text-muted text-decoration-none">Gestión mora</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-end">Cuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proximosVencimientos as $cuota)
                                @php
                                    $cl = $cuota->venta ? $cuota->venta->cliente : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark small">{{ $cl ? $cl->nombres_apellidos : 'Cliente' }}</div>
                                        <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $cl ? ($cl->telefono ?: 'S/T') : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-primary border">
                                            {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m') }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-dark small">${{ number_format($cuota->saldo_restante, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted small">
                                        <i class="fas fa-check-circle text-success me-1"></i> No hay cuotas próximas a vencer en los siguientes 7 días.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/chartM.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gráfico de Barras / Línea de Ingresos
    const ctxIngresos = document.getElementById('chartIngresosDashboard');
    if (ctxIngresos) {
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: {!! json_encode($labelsMeses) !!},
                datasets: [{
                    label: 'Recaudado ($)',
                    data: {!! json_encode($dataIngresosMeses) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.75)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(c) { return ' Recaudación: $' + Number(c.raw).toLocaleString(); }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return '$' + val.toLocaleString(); }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Dona de Lotes
    const ctxLotes = document.getElementById('chartLotesDashboard');
    if (ctxLotes) {
        new Chart(ctxLotes, {
            type: 'doughnut',
            data: {
                labels: ['Disponibles', 'Reservados', 'Vendidos'],
                datasets: [{
                    data: [{{ $lotesDisponibles }}, {{ $lotesReservados }}, {{ $lotesVendidos }}],
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    }
});
</script>
@endsection