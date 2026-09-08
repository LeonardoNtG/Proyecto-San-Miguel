@extends('template')

@section('titulo', 'Monitoreo de Cajas y Cierres en Vivo')

@section('contenido')

<style>
    .kpi-card {
        border-radius: 14px;
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
    }
    .kpi-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .caja-card {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .caja-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .caja-card.caja-activa {
        border-left: 5px solid #10b981;
    }
    .caja-card.caja-cerrada {
        border-left: 5px solid #3b82f6;
    }
    .caja-card.caja-inactiva {
        border-left: 5px solid #94a3b8;
        opacity: 0.85;
    }
    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 1.8s infinite;
    }
    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }
    .stat-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
    }
    .badge-lote {
        background-color: #1e293b;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 3px 7px;
        border-radius: 5px;
    }
</style>

<div class="container-fluid py-3">

    {{-- CABECERA Y FILTRO DE FECHA --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Panel Administrativo</span>
                        <span class="text-muted small"><i class="fas fa-clock me-1"></i> Actualizado: {{ now()->format('h:i A') }}</span>
                    </div>
                    <h3 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-cash-register text-primary"></i> Monitoreo de Cajas y Cierres en Vivo
                    </h3>
                    <p class="text-muted small mb-0 mt-1">
                        Control en tiempo real de recaudación, efectivo en gaveta y estado de turnos por cada cajero.
                    </p>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                    {{-- Accesos rápidos de fecha --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('reportes.monitor_cajas', ['fecha' => \Carbon\Carbon::today()->format('Y-m-d')]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::today()->format('Y-m-d') ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                            Hoy
                        </a>
                        <a href="{{ route('reportes.monitor_cajas', ['fecha' => \Carbon\Carbon::yesterday()->format('Y-m-d')]) }}" 
                           class="btn {{ $fecha == \Carbon\Carbon::yesterday()->format('Y-m-d') ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                            Ayer
                        </a>
                    </div>

                    {{-- Selector de Fecha y Usuario --}}
                    <form method="GET" action="{{ route('reportes.monitor_cajas') }}" class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="date" name="fecha" value="{{ $fecha }}" class="form-control form-control-sm" onchange="this.form.submit()" style="width: 145px;">
                        
                        <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 170px;">
                            <option value="">-- Todos los Usuarios --</option>
                            @foreach($todosLosUsuarios as $u)
                                <option value="{{ $u->id }}" {{ $filtroUsuarioId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- TARJETAS DE RESUMEN GLOBAL (KPIS) --}}
    <div class="row g-3 mb-4">
        {{-- Total Recaudado Global --}}
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm kpi-card bg-white p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Recaudación Global (Día)</div>
                        <div class="fs-4 fw-bold text-dark mt-1">U$ {{ number_format($kpis['totalRecaudadoGlobal'], 2) }}</div>
                        <div class="small text-muted mt-1">
                            <span class="text-success fw-semibold"><i class="fas fa-money-bill-wave me-1"></i>Efectivo:</span> U$ {{ number_format($kpis['totalEfectivoGlobal'], 2) }}
                        </div>
                    </div>
                    <div class="kpi-icon-box bg-primary-subtle text-primary">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Bancos / Transferencias --}}
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm kpi-card bg-white p-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Bancos y Depósitos</div>
                        <div class="fs-4 fw-bold text-info mt-1">U$ {{ number_format($kpis['totalBancosGlobal'], 2) }}</div>
                        <div class="small text-muted mt-1">
                            <i class="fas fa-university me-1"></i> Transferencias / Cheques
                        </div>
                    </div>
                    <div class="kpi-icon-box bg-info-subtle text-info">
                        <i class="fas fa-building-columns"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Egresos Global --}}
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm kpi-card bg-white p-3 h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Egresos (Salidas)</div>
                        <div class="fs-4 fw-bold text-danger mt-1">U$ {{ number_format($kpis['totalEgresosGlobal'], 2) }}</div>
                        <div class="small text-muted mt-1">
                            <span class="fw-semibold text-dark">Flujo Neto:</span> U$ {{ number_format($kpis['flujoNetoGlobal'], 2) }}
                        </div>
                    </div>
                    <div class="kpi-icon-box bg-danger-subtle text-danger">
                        <i class="fas fa-arrow-trend-down"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estado de Cajas --}}
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm kpi-card bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Estado Operativo</div>
                        <div class="fs-4 fw-bold text-success mt-1">
                            {{ $kpis['totalCajasAbiertas'] }} <span class="fs-6 text-muted fw-normal">Cajas en Vivo</span>
                        </div>
                        <div class="small text-muted mt-1">
                            <span class="text-primary fw-semibold">{{ $kpis['totalCierresRealizados'] }}</span> cierres finalizados hoy
                        </div>
                    </div>
                    <div class="kpi-icon-box bg-success-subtle text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MONITOREO DETALLADO POR USUARIO --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-users-gear text-secondary me-2"></i> Estado de Cajas por Usuario ({{ count($usuariosData) }})
        </h5>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="fas fa-circle text-success me-1"></i> Turno Abierto (En Vivo)</span>
            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1"><i class="fas fa-check-circle text-primary me-1"></i> Turno Cerrado</span>
            <span class="badge bg-light text-muted border px-2 py-1"><i class="fas fa-minus-circle text-muted me-1"></i> Sin Apertura</span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @forelse($usuariosData as $data)
            @php
                $u = $data['user'];
                $estado = $data['estado'];
                $cardClass = $estado === 'EN_VIVO' ? 'caja-activa' : ($estado === 'CERRADO' ? 'caja-cerrada' : 'caja-inactiva');
            @endphp

            <div class="col-xl-6 col-lg-12">
                <div class="card shadow-sm caja-card {{ $cardClass }} h-100 p-3">
                    
                    {{-- CABECERA DEL CAJERO --}}
                    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle bg-dark text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                {{ strtoupper(substr($u->name, 0, 2)) }}
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">{{ $u->name }}</h5>
                                <div class="text-muted small">
                                    <span class="badge bg-secondary-subtle text-dark border me-1">{{ $u->roles->pluck('name')->first() ?? 'Usuario' }}</span>
                                    <span>{{ $u->email }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            @if($estado === 'EN_VIVO')
                                <span class="badge bg-success text-white px-3 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                                    <span class="pulse-dot"></span> TURNO EN VIVO
                                </span>
                            @elseif($estado === 'CERRADO')
                                <span class="badge bg-primary text-white px-3 py-2 fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="fas fa-lock me-1"></i> TURNO CERRADO
                                </span>
                            @else
                                <span class="badge bg-light text-muted border px-2.5 py-1.5 fw-semibold">
                                    <i class="fas fa-door-closed me-1"></i> Sin Apertura
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- RESUMEN FINANCIERO DEL TURNO / DÍA --}}
                    @if($estado === 'EN_VIVO')
                        {{-- BLOQUE EN VIVO: CÓMO VA QUEDANDO EL CIERRE --}}
                        <div class="alert alert-success bg-success-subtle border-success-subtle py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-success">
                                <i class="fas fa-play-circle me-1"></i> Turno abierto a las: {{ $data['ultimaApertura']->created_at->format('h:i A') }}
                            </span>
                            <span class="badge bg-success text-white fw-bold">
                                {{ $data['cantAbonosDia'] }} {{ $data['cantAbonosDia'] == 1 ? 'pago recibido' : 'pagos recibidos' }}
                            </span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-4">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Fondo Inicial</div>
                                    <div class="fw-bold text-dark">U$ {{ number_format($data['montoInicialTurno'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Efectivo Cobrado</div>
                                    <div class="fw-bold text-success">+ U$ {{ number_format($data['ingresosEfectivoTurno'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Egresos Efectivo</div>
                                    <div class="fw-bold text-danger">- U$ {{ number_format($data['salidasEfectivoTurno'], 2) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- EFECTIVO ESPERADO EN GAVETA (CIERRE EN TIEMPO REAL) --}}
                        <div class="card bg-dark text-white border-0 p-3 mb-3 shadow-sm rounded-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-uppercase small fw-bold text-warning" style="letter-spacing: 0.5px;">
                                        <i class="fas fa-calculator me-1"></i> Cierre Estimado en Vivo (Efectivo en Gaveta)
                                    </div>
                                    <div class="fs-3 fw-bold text-white mt-1">
                                        U$ {{ number_format($data['efectivoEnGaveta'], 2) }}
                                    </div>
                                    <div class="small text-white-50">
                                        (Base: ${{ number_format($data['montoInicialTurno'], 2) }} + Efectivo: ${{ number_format($data['ingresosEfectivoTurno'], 2) }} - Salidas: ${{ number_format($data['salidasEfectivoTurno'], 2) }})
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-white-50">Bancos / Transf.</div>
                                    <div class="fs-5 fw-bold text-info">U$ {{ number_format($data['ingresosBancosTurno'], 2) }}</div>
                                    <div class="small text-white-50">Total Turno: U$ {{ number_format($data['totalIngresosTurno'], 2) }}</div>
                                </div>
                            </div>
                        </div>

                    @elseif($estado === 'CERRADO')
                        @php $cierre = $data['ultimoCierre']; @endphp
                        {{-- BLOQUE CERRADO: RESULTADOS DEL ARQUEO --}}
                        <div class="alert alert-primary bg-primary-subtle border-primary-subtle py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-primary">
                                <i class="fas fa-lock me-1"></i> Turno cerrado a las: {{ $cierre->created_at->format('h:i A') }}
                            </span>
                            <span class="badge bg-primary text-white fw-bold">
                                Cierre #{{ $cierre->id }}
                            </span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-3">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Saldo Inicial</div>
                                    <div class="fw-bold text-dark">U$ {{ number_format($cierre->saldo_inicial, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Ingresos</div>
                                    <div class="fw-bold text-success">+ U$ {{ number_format($cierre->ingresos, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Egresos</div>
                                    <div class="fw-bold text-danger">- U$ {{ number_format($cierre->egresos, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="stat-pill text-center">
                                    <div class="text-muted small">Saldo Sistema</div>
                                    <div class="fw-bold text-dark">U$ {{ number_format($cierre->saldo_final, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- RESULTADO DEL ARQUEO DECLARADO --}}
                        <div class="card border p-3 mb-3 rounded-3 {{ $cierre->diferencia != 0 ? 'bg-warning-subtle border-warning' : 'bg-light' }}">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="small text-muted fw-bold">Efectivo Real Declarado:</div>
                                    <div class="fs-4 fw-bold text-dark">U$ {{ number_format($cierre->efectivo_real, 2) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted fw-bold">Diferencia de Arqueo:</div>
                                    @if($cierre->diferencia == 0)
                                        <span class="badge bg-success fs-6 px-3 py-1.5"><i class="fas fa-check me-1"></i> Cuadrado (U$ 0.00)</span>
                                    @elseif($cierre->diferencia > 0)
                                        <span class="badge bg-info text-dark fs-6 px-3 py-1.5"><i class="fas fa-plus me-1"></i> Sobrante (+U$ {{ number_format($cierre->diferencia, 2) }})</span>
                                    @else
                                        <span class="badge bg-danger fs-6 px-3 py-1.5"><i class="fas fa-triangle-exclamation me-1"></i> Faltante (-U$ {{ number_format(abs($cierre->diferencia), 2) }})</span>
                                    @endif
                                </div>
                            </div>
                            @if(!empty($cierre->comentario))
                                <div class="small text-muted mt-2 pt-2 border-top">
                                    <strong>Justificación:</strong> <em>"{{ $cierre->comentario }}"</em>
                                </div>
                            @endif
                        </div>

                    @else
                        {{-- SIN APERTURA REGISTRADA HOY --}}
                        <div class="text-center py-4 text-muted bg-light rounded-3 mb-3">
                            <i class="fas fa-user-clock fs-2 mb-2 text-secondary"></i>
                            <p class="mb-1 fw-bold">El usuario no ha abierto caja en esta fecha.</p>
                            <span class="small text-muted">No se registran movimientos ni aperturas activas.</span>
                        </div>
                    @endif

                    {{-- ACCIONES Y DESPLEGABLE DE RECIBOS --}}
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top flex-wrap gap-2">
                        <div>
                            @if($data['abonosDia']->count() > 0 || $data['salidasDia']->count() > 0)
                                <button class="btn btn-sm btn-outline-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAbonos{{ $u->id }}" aria-expanded="false">
                                    <i class="fas fa-list-check me-1"></i> Ver Movimientos ({{ $data['abonosDia']->count() }} pagos, {{ $data['salidasDia']->count() }} egresos)
                                </button>
                            @else
                                <span class="text-muted small"><i class="fas fa-circle-info me-1"></i> Sin transacciones hoy</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            @if($estado === 'CERRADO' && $data['ultimoCierre'])
                                <a href="{{ route('reportes.cierre_turno.pdf', $data['ultimoCierre']->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Imprimir PDF del Cierre de Turno">
                                    <i class="fas fa-file-pdf me-1"></i> Cierre PDF
                                </a>
                            @endif

                            <a href="{{ route('reportes.cierre_caja', ['fecha' => $fecha, 'user_id' => $u->id]) }}" class="btn btn-sm btn-light border text-dark fw-semibold" title="Ver Reporte Diario Detallado de este Usuario">
                                <i class="fas fa-arrow-up-right-from-square me-1"></i> Reporte Diario
                            </a>
                        </div>
                    </div>

                    {{-- LISTADO DESPLEGABLE DE PAGOS Y EGRESOS REGISTRADOS --}}
                    @if($data['abonosDia']->count() > 0 || $data['salidasDia']->count() > 0)
                        <div class="collapse mt-3" id="collapseAbonos{{ $u->id }}">
                            <div class="card card-body bg-light border-0 p-2">
                                <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i class="fas fa-receipt text-primary me-1"></i> Pagos Registrados Hoy:</h6>
                                
                                @if($data['abonosDia']->count() > 0)
                                    <div class="table-responsive mb-2" style="max-height: 220px; overflow-y: auto;">
                                        <table class="table table-sm table-hover bg-white border mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Recibo</th>
                                                    <th>Cliente</th>
                                                    <th>Lote(s)</th>
                                                    <th>Método</th>
                                                    <th class="text-end">Monto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['abonosDia'] as $ab)
                                                    @php
                                                        $clienteNombre = $ab->venta && $ab->venta->cliente ? $ab->venta->cliente->nombres_apellidos : 'N/A';
                                                        $lotesTxt = '';
                                                        if ($ab->venta && $ab->venta->lotes) {
                                                            $lotesTxt = $ab->venta->lotes->pluck('numero_lote')->implode(', ');
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="text-muted">{{ $ab->created_at ? $ab->created_at->format('h:i A') : '-' }}</td>
                                                        <td class="fw-bold text-primary">{{ $ab->numero_recibo ?? 'REC-' . $ab->id }}</td>
                                                        <td>{{ Str::limit($clienteNombre, 18) }}</td>
                                                        <td><span class="badge-lote">{{ $lotesTxt ?: '-' }}</span></td>
                                                        <td>
                                                            @if($ab->metodo_pago === 'Efectivo')
                                                                <span class="badge bg-success-subtle text-success border border-success">Efectivo</span>
                                                            @else
                                                                <span class="badge bg-info-subtle text-info border border-info">{{ $ab->metodo_pago }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">U$ {{ number_format($ab->monto_abonado, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-2">No hay pagos registrados.</p>
                                @endif

                                @if($data['salidasDia']->count() > 0)
                                    <h6 class="fw-bold text-dark mb-2 small text-uppercase mt-2"><i class="fas fa-arrow-down-from-bracket text-danger me-1"></i> Egresos Registrados:</h6>
                                    <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                                        <table class="table table-sm table-hover bg-white border mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Descripción</th>
                                                    <th>Método</th>
                                                    <th class="text-end">Monto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['salidasDia'] as $sal)
                                                    <tr>
                                                        <td class="text-muted">{{ $sal->created_at ? $sal->created_at->format('h:i A') : '-' }}</td>
                                                        <td>{{ $sal->descripcion }}</td>
                                                        <td>{{ $sal->metodo_pago ?: 'Efectivo' }}</td>
                                                        <td class="text-end fw-bold text-danger">- U$ {{ number_format($sal->monto, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">No se encontraron usuarios en el sistema.</p>
            </div>
        @endforelse
    </div>

    {{-- TABLA COMPARATIVA CONSOLIDADA DE TODOS LOS USUARIOS --}}
    <div class="card shadow-sm border-0 bg-white mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-table-list text-primary me-2"></i> Tabla Resumen Consolidada por Cajero ({{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }})
            </h5>
            <span class="badge bg-secondary-subtle text-dark border">{{ count($usuariosData) }} usuarios</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Usuario / Cajero</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Fondo Base</th>
                            <th class="text-end">Efectivo Cobrado</th>
                            <th class="text-end">Bancos / Transf.</th>
                            <th class="text-end">Total Recaudado</th>
                            <th class="text-end">Egresos</th>
                            <th class="text-end">Efectivo en Caja</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuariosData as $data)
                            @php
                                $u = $data['user'];
                                $estado = $data['estado'];
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-light border text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($u->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div>{{ $u->name }}</div>
                                            <div class="small text-muted fw-normal">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($estado === 'EN_VIVO')
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 fw-bold">
                                            <span class="pulse-dot me-1"></span> En Vivo
                                        </span>
                                    @elseif($estado === 'CERRADO')
                                        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold">
                                            <i class="fas fa-lock me-1"></i> Cerrado
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1">Sin Apertura</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted">
                                    U$ {{ number_format($data['montoInicialTurno'], 2) }}
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    + U$ {{ number_format($data['diaEfectivo'], 2) }}
                                </td>
                                <td class="text-end fw-semibold text-info">
                                    + U$ {{ number_format($data['diaBancos'], 2) }}
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    U$ {{ number_format($data['diaTotalRecaudado'], 2) }}
                                </td>
                                <td class="text-end fw-semibold text-danger">
                                    - U$ {{ number_format($data['diaTotalEgresos'], 2) }}
                                </td>
                                <td class="text-end fw-bold {{ $estado === 'EN_VIVO' ? 'text-primary fs-6' : 'text-dark' }}">
                                    @if($estado === 'EN_VIVO')
                                        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                            U$ {{ number_format($data['efectivoEnGaveta'], 2) }}
                                        </span>
                                    @elseif($estado === 'CERRADO')
                                        <span>U$ {{ number_format($data['ultimoCierre']->efectivo_real, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('reportes.cierre_caja', ['fecha' => $fecha, 'user_id' => $u->id]) }}" class="btn btn-outline-secondary" title="Ver Cierre Diario">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($estado === 'CERRADO' && $data['ultimoCierre'])
                                            <a href="{{ route('reportes.cierre_turno.pdf', $data['ultimoCierre']->id) }}" target="_blank" class="btn btn-outline-danger" title="PDF Cierre">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3" colspan="2">TOTALES GLOBALES ({{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }})</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-success">U$ {{ number_format($kpis['totalEfectivoGlobal'], 2) }}</td>
                            <td class="text-end text-info">U$ {{ number_format($kpis['totalBancosGlobal'], 2) }}</td>
                            <td class="text-end text-dark fs-6">U$ {{ number_format($kpis['totalRecaudadoGlobal'], 2) }}</td>
                            <td class="text-end text-danger">U$ {{ number_format($kpis['totalEgresosGlobal'], 2) }}</td>
                            <td class="text-end text-primary fs-6">Flujo Neto: U$ {{ number_format($kpis['flujoNetoGlobal'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
