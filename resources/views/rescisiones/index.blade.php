@extends('template')

@section('titulo', '| Historial de Rescisiones y Desistimientos')

@section('contenido')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-primary fw-bold mb-0">
            <i class="fas fa-undo-alt me-2"></i> Historial de Rescisiones y Desistimientos
        </h2>
        <p class="text-muted mb-0 mt-1">Bitácora completa de lotes liberados a disponibilidad, destino de saldos y comentarios.</p>
    </div>
</div>

{{-- Barra de Búsqueda y Filtros --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('rescisiones.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control"
                        placeholder="Buscar por cliente, cédula, lote o comentario..."
                        value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="tipo" class="form-select">
                    <option value="">— Todos los tipos —</option>
                    <option value="Parcial" {{ ($tipo ?? '') == 'Parcial' ? 'selected' : '' }}>Rescisión Parcial</option>
                    <option value="Total" {{ ($tipo ?? '') == 'Total' ? 'selected' : '' }}>Rescisión Total</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="destino" class="form-select">
                    <option value="">— Destino de Abonos —</option>
                    <option value="acreditar_otro_lote" {{ ($destino ?? '') == 'acreditar_otro_lote' ? 'selected' : '' }}>Acreditado a lote conservado</option>
                    <option value="devolucion_efectivo" {{ ($destino ?? '') == 'devolucion_efectivo' ? 'selected' : '' }}>Devolución en efectivo</option>
                    <option value="sin_devolucion" {{ ($destino ?? '') == 'sin_devolucion' ? 'selected' : '' }}>Sin devolución</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Filtrar">
                    <i class="fas fa-filter"></i>
                </button>
                @if($search || $tipo || $destino)
                    <a href="{{ route('rescisiones.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($rescisiones->count() > 0)
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 70px;">#</th>
                        <th>Fecha / Hora</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Lotes Desistidos (Disponibles)</th>
                        <th>Lotes Conservados</th>
                        <th>Destino de lo Abonado</th>
                        <th>Comentario / Motivo</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rescisiones as $r)
                    <tr>
                        <td class="text-muted fw-bold small">#{{ $r->id_rescision }}</td>
                        <td class="small">
                            {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}<br>
                            <span class="text-muted" style="font-size: 0.78rem;">{{ \Carbon\Carbon::parse($r->created_at)->format('h:i A') }}</span>
                        </td>
                        <td>
                            @if($r->cliente)
                                <strong>{{ $r->cliente->nombres_apellidos }}</strong><br>
                                <small class="text-muted">{{ $r->cliente->identificacion }}</small><br>
                                <a href="{{ route('registro.show', $r->cliente->id_cliente) }}"
                                   class="badge bg-light text-primary border text-decoration-none mt-1" target="_blank">
                                    <i class="fas fa-user me-1"></i> Ver Ficha
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $r->tipo == 'Parcial' ? 'bg-warning text-dark' : 'bg-danger' }}">
                                {{ $r->tipo }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border border-danger fw-bold py-1 px-2">
                                <i class="fas fa-undo me-1"></i> {{ $r->lotes_afectados }}
                            </span>
                            <br><small class="text-success"><i class="fas fa-check-circle me-1"></i> Lote liberado (Disponible)</small>
                        </td>
                        <td class="small">
                            @if($r->lotes_conservados)
                                <span class="badge bg-success-subtle text-success border border-success">
                                    <i class="fas fa-shield-alt me-1"></i> {{ $r->lotes_conservados }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($r->destino_abonos == 'acreditar_otro_lote')
                                <span class="badge bg-success py-1 px-2">
                                    <i class="fas fa-arrow-right me-1"></i> Acreditado a lote conservado
                                </span>
                                <div class="small fw-bold text-success mt-1">
                                    ${{ number_format($r->monto_transferido, 2) }}
                                </div>
                                @if($r->id_venta_destino)
                                    <small class="text-muted d-block">Contrato Destino #{{ $r->id_venta_destino }}</small>
                                @endif
                            @elseif($r->destino_abonos == 'devolucion_efectivo')
                                <span class="badge bg-danger py-1 px-2">
                                    <i class="fas fa-money-bill-wave me-1"></i> Devolución en efectivo
                                </span>
                                <div class="small fw-bold text-danger mt-1">
                                    ${{ number_format($r->monto_devuelto, 2) }}
                                </div>
                            @else
                                <span class="badge bg-secondary py-1 px-2">
                                    Sin devolución
                                </span>
                            @endif
                        </td>
                        <td style="max-width: 260px;">
                            <div class="p-2 bg-light rounded small border border-1" style="white-space: pre-wrap; word-break: break-word;">
                                <i class="fas fa-comment-dots text-primary me-1"></i>
                                {{ $r->comentario }}
                            </div>
                        </td>
                        <td class="small text-muted">
                            {{ $r->user ? $r->user->name : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $rescisiones->appends(request()->query())->links() }}
</div>

@else
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
            <i class="fas fa-undo-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No se encontraron rescisiones o desistimientos registrados.</h5>
            @if($search || $tipo || $destino)
                <p class="text-muted">No hay resultados con los filtros actuales.</p>
                <a href="{{ route('rescisiones.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-redo me-1"></i> Restablecer filtros
                </a>
            @endif
        </div>
    </div>
@endif

@endsection
