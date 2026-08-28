@extends('template')

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0"><i class="fas fa-history text-primary"></i> Bitácora de Auditoría</h2>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Registro de movimientos críticos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Módulo</th>
                            <th>Detalles</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditorias as $log)
                        <tr>
                            <td>
                                <span class="d-block fw-bold">{{ $log->created_at->format('d/m/Y') }}</span>
                                <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                @if($log->user)
                                    <i class="fas fa-user-circle text-secondary"></i> {{ $log->user->name }}
                                @else
                                    <span class="text-muted">Sistema / Eliminado</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info text-dark">{{ $log->accion }}</span></td>
                            <td>
                                @if($log->modelo)
                                    <strong>{{ $log->modelo }}</strong> (ID: {{ $log->modelo_id }})
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $log->detalles }}</td>
                            <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                <h5>No hay registros de auditoría aún.</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                {{ $auditorias->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection
