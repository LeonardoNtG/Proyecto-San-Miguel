@extends('template') 

@section('titulo', 'Lotificaciones') 

@section('contenido') 
<div class="container-fluid px-4 py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión de Proyectos (Lotificaciones)</h3>
            <p class="text-muted small mb-0">Administra los datos, RUC, logo y contacto de cada lotificación para los recibos</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-custom shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Proyecto</th>
                            <th>RUC</th>
                            <th>Teléfono</th>
                            <th>Ciudad</th>
                            <th>Logo</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lotificaciones as $lot)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $lot->nombre }}</td>
                                <td>{{ $lot->ruc ?? 'No especificado' }}</td>
                                <td>{{ $lot->telefono ?? 'No especificado' }}</td>
                                <td>{{ $lot->ciudad ?? 'No especificado' }}</td>
                                <td>
                                    @if($lot->logo)
                                        <img src="{{ asset('storage/'.$lot->logo) }}" alt="Logo" style="height: 30px; object-fit: contain;">
                                    @else
                                        <span class="text-muted small">Sin logo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('lotificaciones.edit', $lot->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Editar Configuración
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No hay proyectos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
