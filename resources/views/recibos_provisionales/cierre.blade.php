@extends('template')

@section('titulo', 'Cierre Diario de Recibos Provisionales')

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
    .badge-recibo-prov {
        background-color: #f59e0b !important;
        color: #1e293b !important;
        font-weight: 700;
        font-size: 0.88rem;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #d97706;
    }
    .badge-en-blanco {
        background-color: #f1f5f9 !important;
        color: #64748b !important;
        border: 1px dashed #94a3b8 !important;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
    @media print {
        .no-print { display: none !important; }
        .sidebar, .navbar, footer { display: none !important; }
        .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    }
</style>

<div class="container-fluid py-3">

    {{-- CABECERA CON FILTROS Y ACCIONES --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="mb-1 fw-bold" style="color: #1e293b !important;">
                        <i class="fas fa-file-invoice-dollar text-warning me-2"></i> Reporte de Cierre Diario
                    </h3>
                    <p class="text-muted small mb-0">
                        Corte diario de operaciones del: <strong class="text-dark">{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</strong>
                    </p>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2 no-print">
                    {{-- Accesos rápidos de fecha --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('recibos_provisionales.cierre', ['fecha' => \Carbon\Carbon::today()->format('Y-m-d'), 'lotificacion_id' => $lotificacionId]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::today()->format('Y-m-d') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">
                            Hoy
                        </a>
                        <a href="{{ route('recibos_provisionales.cierre', ['fecha' => \Carbon\Carbon::yesterday()->format('Y-m-d'), 'lotificacion_id' => $lotificacionId]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::yesterday()->format('Y-m-d') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">
                            Ayer
                        </a>
                    </div>

                    {{-- Formulario de Filtros --}}
                    <form method="GET" action="{{ route('recibos_provisionales.cierre') }}" class="d-flex align-items-center gap-2">
                        <input type="date" name="fecha" value="{{ $fecha }}" class="form-control form-control-sm" style="max-width: 150px;" onchange="this.form.submit()">
                        <select name="lotificacion_id" class="form-select form-select-sm" style="max-width: 200px;" onchange="this.form.submit()">
                            <option value="">-- Todos los Proyectos --</option>
                            @foreach($lotificaciones as $lot)
                                <option value="{{ $lot->id }}" {{ $lotificacionId == $lot->id ? 'selected' : '' }}>
                                    {{ $lot->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    {{-- Botones de Acción --}}
                    <a href="{{ route('recibos_provisionales.cierre.pdf', ['fecha' => $fecha, 'lotificacion_id' => $lotificacionId]) }}" target="_blank" class="btn btn-sm btn-outline-danger fw-bold shadow-sm">
                        <i class="fas fa-print me-1"></i> Imprimir Reporte PDF
                    </a>
                    <a href="{{ route('recibos_provisionales.index') }}" class="btn btn-sm btn-secondary fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Recibos
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- TARJETAS KPI RESUMEN --}}
    <div class="row g-3 mb-4">
        {{-- Total Recaudado --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card shadow-sm text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small fw-bold text-uppercase">Total Recaudado</div>
                            <h2 class="fw-bold mb-0 mt-1">${{ number_format($totalMonto, 2) }}</h2>
                            <span class="small text-white-50">{{ $recibosConMonto }} recibos con monto</span>
                        </div>
                        <div class="kpi-icon-badge">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Recibos Emitidos --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card shadow-sm text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small fw-bold text-uppercase">Recibos Emitidos</div>
                            <h2 class="fw-bold mb-0 mt-1">{{ $totalRecibos }}</h2>
                            <span class="small text-white-50">Corte del día</span>
                        </div>
                        <div class="kpi-icon-badge">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recibos Completados con Monto --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card shadow-sm text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small fw-bold text-uppercase">Con Monto / Abono</div>
                            <h2 class="fw-bold mb-0 mt-1">{{ $recibosConMonto }}</h2>
                            <span class="small text-white-50">Amparados con dinero</span>
                        </div>
                        <div class="kpi-icon-badge">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recibos Emitidos en Blanco --}}
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card shadow-sm text-white" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small fw-bold text-uppercase">En Blanco / Manuales</div>
                            <h2 class="fw-bold mb-0 mt-1">{{ $recibosEnBlanco }}</h2>
                            <span class="small text-white-50">Para llenado a mano</span>
                        </div>
                        <div class="kpi-icon-badge">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DETALLADA DE RECIBOS PROVISIONALES --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold" style="color: #1e293b;">
                <i class="fas fa-list-ol text-warning me-2"></i> Detalle de Recibos Provisionales del Día
            </h5>
            <span class="badge bg-light text-dark border">
                Total Registros: {{ $totalRecibos }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead style="background-color: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="text-center" style="width: 110px;">N° Recibo</th>
                            <th style="width: 90px;">Hora</th>
                            <th>Proyecto</th>
                            <th>Cliente / Recibimos de</th>
                            <th>Concepto</th>
                            <th>Motivo / Justificación</th>
                            <th>Cajero / Usuario</th>
                            <th class="text-end" style="width: 140px;">Monto ($)</th>
                            <th class="text-center no-print" style="width: 90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recibos as $recibo)
                            <tr>
                                <td class="text-center">
                                    <span class="badge-recibo-prov">
                                        {{ $recibo->numero_recibo_formateado }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $recibo->created_at ? $recibo->created_at->format('H:i A') : '--:--' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        {{ $recibo->lotificacion?->nombre ?? 'Sin Proyecto' }}
                                    </span>
                                </td>
                                <td>
                                    @if($recibo->cliente_nombre)
                                        <strong class="text-dark">{{ $recibo->cliente_nombre }}</strong>
                                    @else
                                        <span class="badge-en-blanco"><i class="fas fa-pen me-1"></i>En Blanco</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">
                                        {{ $recibo->concepto ?: '—' }}
                                    </span>
                                    @if(!is_null($recibo->total_abonado) || !is_null($recibo->saldo_pendiente))
                                        <div class="small text-muted mt-1">
                                            @if(!is_null($recibo->valor_total)) Total: <strong>${{ number_format($recibo->valor_total, 2) }}</strong> | @endif
                                            @if(!is_null($recibo->total_abonado)) Abono: <strong>${{ number_format($recibo->total_abonado, 2) }}</strong> | @endif
                                            @if(!is_null($recibo->saldo_pendiente)) Saldo: <strong>${{ number_format($recibo->saldo_pendiente, 2) }}</strong> @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        {{ $recibo->motivo ?: 'Sin observación' }}
                                    </small>
                                </td>
                                <td>
                                    <span class="small text-muted">
                                        <i class="fas fa-user-circle me-1"></i>{{ $recibo->user?->name ?? 'Sistema' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if(!is_null($recibo->monto) && (float)$recibo->monto > 0)
                                        <span class="fw-bold text-success fs-6">
                                            ${{ number_format($recibo->monto, 2) }}
                                        </span>
                                    @else
                                        <span class="badge-en-blanco">En Blanco</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('recibos_provisionales.imprimir', $recibo->id_recibo_provisional) }}" target="_blank" class="btn btn-sm btn-outline-warning text-dark" title="Imprimir Recibo">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-invoice fa-3x text-muted mb-3 d-block opacity-50"></i>
                                    <h6 class="fw-bold text-secondary">No se registraron recibos provisionales en esta fecha</h6>
                                    <p class="small text-muted mb-0">Selecciona otra fecha o emite un recibo provisional para ver los movimientos.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($totalRecibos > 0)
                        <tfoot style="background-color: #f8fafc; border-top: 2px solid #e2e8f0;">
                            <tr>
                                <th colspan="7" class="text-end fw-bold fs-6">TOTAL GENERAL RECAUDADO:</th>
                                <th class="text-end fw-bold fs-5 text-success">${{ number_format($totalMonto, 2) }}</th>
                                <th class="no-print"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- RESUMEN POR PROYECTO Y POR CAJERO --}}
    @if($totalRecibos > 0)
        <div class="row g-3">
            {{-- Por Proyecto --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold" style="color: #1e293b;">
                            <i class="fas fa-building text-primary me-2"></i> Resumen por Proyecto / Lotificación
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Proyecto</th>
                                    <th class="text-center">Recibos</th>
                                    <th class="text-end">Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($porProyecto as $item)
                                    <tr>
                                        <td><strong>{{ $item['nombre'] }}</strong></td>
                                        <td class="text-center"><span class="badge bg-secondary">{{ $item['cantidad'] }}</span></td>
                                        <td class="text-end fw-bold text-success">${{ number_format($item['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Por Cajero / Usuario --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold" style="color: #1e293b;">
                            <i class="fas fa-users text-info me-2"></i> Resumen por Cajero / Usuario
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cajero / Usuario</th>
                                    <th class="text-center">Recibos</th>
                                    <th class="text-end">Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($porUsuario as $item)
                                    <tr>
                                        <td><strong>{{ $item['nombre'] }}</strong></td>
                                        <td class="text-center"><span class="badge bg-secondary">{{ $item['cantidad'] }}</span></td>
                                        <td class="text-end fw-bold text-success">${{ number_format($item['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@endsection
