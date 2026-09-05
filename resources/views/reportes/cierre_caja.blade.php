@extends('template')

@section('titulo', 'Reporte Diario y Cierre de Caja')

@section('contenido')

<style>
    .kpi-card {
        border-radius: 12px;
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.1) !important;
    }
    .kpi-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
    }
    .badge-lote {
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 5px 9px;
        border-radius: 6px;
        display: inline-block;
    }
    .badge-metodo-efectivo {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #10b981 !important;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 6px;
        display: inline-block;
    }
    .badge-metodo-banco {
        background-color: #dbeafe !important;
        color: #1e40af !important;
        border: 1px solid #3b82f6 !important;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 6px;
        display: inline-block;
    }
    .badge-metodo-otro {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 6px;
        display: inline-block;
    }
    @media print {
        .no-print { display: none !important; }
        .sidebar, .navbar, footer { display: none !important; }
        .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    }
</style>

<div class="container-fluid py-3">

    {{-- CABECERA CON FILTRO DE FECHA Y ACCIONES --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="mb-1 fw-bold" style="color: #1e293b !important;">
                        <i class="fas fa-calculator text-primary me-2"></i> Reporte Diario / Cierre de Caja
                    </h3>
                    <p class="text-muted small mb-0">
                        Movimientos registrados del día: <strong class="text-dark">{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</strong>
                    </p>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2 no-print">
                    {{-- Accesos rápidos de fecha --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('reportes.cierre_caja', ['fecha' => \Carbon\Carbon::today()->format('Y-m-d')]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::today()->format('Y-m-d') ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                            Hoy
                        </a>
                        <a href="{{ route('reportes.cierre_caja', ['fecha' => \Carbon\Carbon::yesterday()->format('Y-m-d')]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::yesterday()->format('Y-m-d') ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                            Ayer
                        </a>
                    </div>

                    {{-- Selector de Fecha --}}
                    <form method="GET" action="{{ route('reportes.cierre_caja') }}" class="d-flex align-items-center gap-1">
                        <input type="date" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control form-control-sm" onchange="this.form.submit()">
                        <button type="submit" class="btn btn-sm btn-primary" title="Consultar Fecha">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    {{-- Botón Imprimir Reporte PDF Oficial --}}
                    <a href="{{ route('reportes.cierre_caja.pdf', ['fecha' => $fecha]) }}" target="_blank" class="btn btn-sm btn-dark fw-bold px-3 ms-1 shadow-sm">
                        <i class="fas fa-file-pdf text-danger me-1"></i> Imprimir Reporte PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- CARDS DE RESUMEN EJECUTIVO (KPIs) --}}
    <div class="row g-3 mb-4">
        
        {{-- Total Ingresos --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm kpi-card h-100" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: #ffffff !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px; color: #ffffff !important; opacity: 0.88;">Total Ingresos</span>
                        <h3 class="mb-0 fw-bold mt-1" style="color: #ffffff !important; font-size: 1.75rem;">${{ number_format($totalGeneral, 2) }}</h3>
                        <small style="color: #ffffff !important; opacity: 0.85;">{{ $abonos->count() }} transacción(es)</small>
                    </div>
                    <div class="kpi-icon-badge">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Efectivo --}}
        <div class="col-xl-2 col-md-6 col-6">
            <div class="card shadow-sm kpi-card bg-white border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Efectivo</span>
                    <h4 class="mb-0 fw-bold mt-1" style="color: #10b981 !important; font-size: 1.45rem;">${{ number_format($totales['Efectivo'] ?? 0, 2) }}</h4>
                    <small class="text-muted"><i class="fas fa-money-bill-wave text-success me-1"></i> En Caja</small>
                </div>
            </div>
        </div>

        {{-- Bancos / Transferencia --}}
        <div class="col-xl-2 col-md-6 col-6">
            <div class="card shadow-sm kpi-card bg-white border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Banco / Transf.</span>
                    <h4 class="mb-0 fw-bold mt-1" style="color: #3b82f6 !important; font-size: 1.45rem;">${{ number_format(($totales['Transferencia Bancaria'] ?? 0) + ($totales['Depósito Bancario'] ?? 0), 2) }}</h4>
                    <small class="text-muted"><i class="fas fa-university text-primary me-1"></i> En Bancos</small>
                </div>
            </div>
        </div>

        {{-- Total Egresos --}}
        <div class="col-xl-2 col-md-6 col-6">
            <div class="card shadow-sm kpi-card bg-white border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Total Egresos</span>
                    <h4 class="mb-0 fw-bold mt-1" style="color: #ef4444 !important; font-size: 1.45rem;">-${{ number_format($totalEgresos, 2) }}</h4>
                    <small class="text-muted">{{ $salidas->count() }} gasto(s)</small>
                </div>
            </div>
        </div>

        {{-- Flujo Neto --}}
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card shadow-sm kpi-card h-100" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #ffffff !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px; color: #ffffff !important; opacity: 0.88;">Flujo Neto Diario</span>
                        <h3 class="mb-0 fw-bold mt-1" style="color: #ffffff !important; font-size: 1.75rem;">${{ number_format($flujoNeto, 2) }}</h3>
                        <small style="color: #ffffff !important; opacity: 0.85;">Ingresos - Egresos</small>
                    </div>
                    <div class="kpi-icon-badge">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TABLA DE DETALLE DE INGRESOS (COBROS) --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 fw-bold" style="color: #1e293b !important;">
                <i class="fas fa-hand-holding-usd text-success me-2"></i> Detalle de Ingresos (Cobros del Día)
            </h5>
            <span class="badge px-3 py-2 fw-bold" style="background-color: #d1fae5 !important; color: #065f46 !important; border: 1px solid #10b981 !important; font-size: 0.88rem;">
                Total Cobrado: ${{ number_format($totalGeneral, 2) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="ps-3" style="width: 25%;">Cliente</th>
                            <th style="width: 20%;">Lote(s)</th>
                            <th style="width: 15%;">Concepto</th>
                            <th style="width: 12%;">Método</th>
                            <th style="width: 15%;">Referencia / Comentarios</th>
                            <th class="text-end" style="width: 13%;">Monto</th>
                            <th class="text-center no-print" style="width: 70px;">Recibo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abonos as $abono)
                        <tr>
                            <td class="ps-3">
                                @if($abono->venta && $abono->venta->cliente)
                                    <a href="{{ route('registro.show', $abono->venta->cliente->id_cliente) }}" class="fw-bold text-primary text-decoration-none">
                                        {{ $abono->venta->cliente->nombres_apellidos }}
                                    </a>
                                    <div class="text-muted small">Exp: {{ $abono->venta->cliente->expediente_num ?: 'N/A' }}</div>
                                @else
                                    <span class="text-muted">Cliente no vinculado</span>
                                @endif
                            </td>
                            <td>
                                @if($abono->venta && $abono->venta->lotes)
                                    @foreach($abono->venta->lotes as $lote)
                                        <span class="badge-lote mb-1">
                                            <i class="fas fa-map-marker-alt me-1 text-warning"></i>
                                            Bloque {{ $lote->bloque->nombre ?? '' }} - Lote {{ $lote->numero_lote }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $abono->tipo_pago }}</span>
                            </td>
                            <td>
                                @if($abono->metodo_pago == 'Efectivo')
                                    <span class="badge-metodo-efectivo">
                                        <i class="fas fa-money-bill-wave me-1"></i> Efectivo
                                    </span>
                                @elseif($abono->metodo_pago == 'Transferencia Bancaria')
                                    <span class="badge-metodo-banco">
                                        <i class="fas fa-exchange-alt me-1"></i> Transf.
                                    </span>
                                @elseif($abono->metodo_pago == 'Depósito Bancario')
                                    <span class="badge-metodo-banco">
                                        <i class="fas fa-university me-1"></i> Depósito
                                    </span>
                                @else
                                    <span class="badge-metodo-otro">{{ $abono->metodo_pago }}</span>
                                @endif
                            </td>
                            <td>
                                @if($abono->referencia)
                                    <div class="small text-dark font-monospace">{{ $abono->referencia }}</div>
                                @endif
                                @if($abono->cuenta_destino)
                                    <div class="small text-muted"><i class="fas fa-university text-secondary me-1"></i>{{ $abono->cuenta_destino }}</div>
                                @endif
                                @if($abono->comentario)
                                    <div class="mt-1">
                                        <small class="badge bg-light text-dark border">
                                            <i class="fas fa-comment-dots text-primary me-1"></i>{{ $abono->comentario }}
                                        </small>
                                    </div>
                                @endif
                                @if(!$abono->referencia && !$abono->cuenta_destino && !$abono->comentario)
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <span class="fs-6 fw-bold text-success">+${{ number_format($abono->monto_abonado, 2) }}</span>
                            </td>
                            <td class="text-center no-print">
                                <a href="{{ route('abonos.imprimir', $abono->id_abono) }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Imprimir Recibo">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h6 class="fw-bold">No hay ingresos registrados en esta fecha</h6>
                                <p class="small mb-0">Seleccione otra fecha en el filtro superior para consultar movimientos.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TABLA DE DETALLE DE EGRESOS (GASTOS) --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom border-danger border-2">
            <h5 class="mb-0 fw-bold text-danger">
                <i class="fas fa-arrow-circle-up me-2"></i> Detalle de Egresos (Salidas de Caja)
            </h5>
            <span class="badge px-3 py-2 fw-bold" style="background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #ef4444 !important; font-size: 0.88rem;">
                Total Egresos: -${{ number_format($totalEgresos, 2) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="ps-3" style="width: 15%;">Hora</th>
                            <th style="width: 45%;">Descripción / Motivo del Gasto</th>
                            <th style="width: 20%;">Método de Salida</th>
                            <th class="text-end pe-3" style="width: 20%;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salidas as $salida)
                        <tr>
                            <td class="ps-3 text-muted">
                                <i class="far fa-clock me-1"></i> {{ $salida->created_at->format('h:i A') }}
                            </td>
                            <td>
                                <strong class="text-dark">{{ $salida->descripcion }}</strong>
                            </td>
                            <td>
                                @if($salida->metodo_pago == 'Efectivo')
                                    <span class="badge-metodo-efectivo"><i class="fas fa-money-bill-wave me-1"></i> Efectivo</span>
                                @else
                                    <span class="badge-metodo-banco"><i class="fas fa-exchange-alt me-1"></i> {{ $salida->metodo_pago }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <span class="fs-6 fw-bold text-danger">-${{ number_format($salida->monto, 2) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-75 d-block"></i>
                                <span class="small">No se registraron egresos o salidas en esta fecha.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
