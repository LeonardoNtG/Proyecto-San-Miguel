@extends('template')

@section('titulo', 'Bitácora de Auditoría y Control de Cambios')

@section('contenido')
<style>
    .badge-purple {
        background-color: #6f42c1 !important;
        color: #ffffff !important;
    }
    .card-filter {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .log-detail-box {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.88rem;
        line-height: 1.5;
    }
    .log-detail-box strong {
        color: #1e293b;
    }
</style>

<div class="container-fluid py-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="fas fa-shield-alt text-primary me-2"></i> Bitácora de Auditoría y Control
            </h2>
            <p class="text-muted small mb-0">
                Historial cronológico de cambios en contratos, pagos, rescisiones, egresos y movimientos del sistema.
            </p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm">
                <i class="fas fa-list-ol me-1"></i> {{ $auditorias->total() }} Registros encontrados
            </span>
        </div>
    </div>

    {{-- FILTROS DE BÚSQUEDA AVANZADA --}}
    <div class="card shadow-sm border-0 mb-4 card-filter">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('auditoria.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4 col-12">
                    <label for="q" class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-search me-1"></i> Buscar por Cliente, Lote o Detalle
                    </label>
                    <input type="text" class="form-control form-control-sm" id="q" name="q" value="{{ request('q') }}" placeholder="Ej: Fátima Arteta, Lote G-01, Recibo 1134...">
                </div>

                <div class="col-md-3 col-6">
                    <label for="accion" class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-tasks me-1"></i> Tipo de Acción
                    </label>
                    <select class="form-select form-select-sm" id="accion" name="accion">
                        <option value="">-- Todas las Acciones --</option>
                        @foreach($accionesDisponibles as $acc)
                            @if($acc)
                                <option value="{{ $acc }}" {{ request('accion') == $acc ? 'selected' : '' }}>{{ $acc }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <label for="user_id" class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-user me-1"></i> Responsable
                    </label>
                    <select class="form-select form-select-sm" id="user_id" name="user_id">
                        <option value="">-- Todos los Usuarios --</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <label for="fecha" class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-calendar-alt me-1"></i> Fecha
                    </label>
                    <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="{{ request('fecha') }}">
                </div>

                <div class="col-md-1 col-6 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" title="Filtrar">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['q', 'accion', 'user_id', 'fecha', 'ver_todo']))
                        <a href="{{ route('auditoria.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar Filtros">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>

                <div class="col-12 mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <div class="form-check form-switch small">
                        <input class="form-check-input" type="checkbox" id="ver_todo" name="ver_todo" value="1" {{ request('ver_todo') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label text-muted" for="ver_todo">
                            Mostrar recálculos técnicos automáticos del sistema
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA PRINCIPAL DE REGISTROS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list text-primary me-1"></i> Detalle de Movimientos y Modificaciones
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 140px;">Fecha y Hora</th>
                            <th style="width: 150px;">Responsable</th>
                            <th style="width: 160px;">Acción Realizada</th>
                            <th style="width: 220px;">Cliente / Lote(s)</th>
                            <th>Detalle Específico del Cambio / Movimiento</th>
                            <th style="width: 110px;" class="text-center">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditorias as $log)
                        @php
                            $cliente = $log->cliente;
                            $lotes = $log->lotes;
                        @endphp
                        <tr>
                            {{-- FECHA Y HORA --}}
                            <td>
                                <span class="d-block fw-bold text-dark">
                                    <i class="far fa-calendar-alt text-primary me-1"></i>{{ $log->created_at->format('d/m/Y') }}
                                </span>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i>{{ $log->created_at->format('h:i A') }}
                                </small>
                            </td>

                            {{-- RESPONSABLE --}}
                            <td>
                                @if($log->user)
                                    <span class="badge bg-light text-dark border shadow-sm px-2 py-1">
                                        <i class="fas fa-user-circle text-primary me-1"></i>{{ $log->user->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        <i class="fas fa-robot me-1"></i>Sistema
                                    </span>
                                @endif
                            </td>

                            {{-- ACCIÓN --}}
                            <td>
                                <span class="badge {{ $log->badge_class }} px-2.5 py-1.5 shadow-sm fw-bold">
                                    <i class="{{ $log->icon }} me-1"></i>{{ $log->accion }}
                                </span>
                                @if($log->modelo)
                                    <small class="text-muted d-block mt-1 font-monospace" style="font-size: 0.72rem;">
                                        {{ $log->modelo }} #{{ $log->modelo_id }}
                                    </small>
                                @endif
                            </td>

                            {{-- CLIENTE Y LOTES --}}
                            <td>
                                @if($cliente)
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 210px;">
                                        <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="text-decoration-none text-primary" title="Ver Perfil del Cliente">
                                            <i class="fas fa-user me-1"></i>{{ $cliente->nombres_apellidos }}
                                        </a>
                                    </div>
                                    @if($cliente->expediente_num)
                                        <small class="badge bg-light text-secondary border py-0 px-1" style="font-size: 0.72rem;">
                                            Exp: {{ $cliente->expediente_num }}
                                        </small>
                                    @endif
                                @elseif($log->modelo === 'Cliente' && $log->modelo_id)
                                    <span class="text-muted small">Cliente #{{ $log->modelo_id }}</span>
                                @else
                                    <span class="text-muted small"><i class="fas fa-minus me-1"></i>General / Sistema</span>
                                @endif

                                @if($lotes)
                                    <div class="mt-1">
                                        <span class="badge bg-dark text-white px-2 py-0.5" style="font-size: 0.72rem;">
                                            <i class="fas fa-map-marker-alt text-warning me-1"></i>{{ $lotes }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- DETALLES DEL CAMBIO --}}
                            <td>
                                <div class="log-detail-box shadow-sm">
                                    {!! $log->detalles !!}
                                </div>
                            </td>

                            {{-- IP --}}
                            <td class="text-center">
                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.75rem;">
                                    {{ $log->ip_address ?: 'Local' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                <h5 class="fw-bold">No se encontraron registros de auditoría</h5>
                                <p class="small text-muted mb-0">Prueba ajustando los filtros de búsqueda o fecha.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($auditorias->hasPages())
                <div class="d-flex justify-content-center p-3 bg-light border-top">
                    {{ $auditorias->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
