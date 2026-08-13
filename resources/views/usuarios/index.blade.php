@extends('template') 

@section('titulo', 'Usuarios') 

@section('contenido') 
<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">
<div class="container-fluid px-4 py-3">

    {{-- Encabezado principal --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión de Usuarios</h3>
            <p class="text-muted small mb-0">Administra los accesos y credenciales del personal</p>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary px-3 shadow-sm rounded-pill">
            <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
        </a>
    </div>

    {{-- Alert de Notificación --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tarjeta contenedora de la Tabla --}}
    <div class="card card-custom shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Correo Electrónico</th>
                            <th>Fecha de Registro</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block">{{ $user->name }}</span>
                                            @if(auth()->id() === $user->id)
                                                <span class="badge bg-soft-primary text-primary border border-primary-subtle rounded-pill">Tú (Sesión activa)</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary">{{ $user->email }}</td>
                                <td class="text-muted small">
                                    {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar credenciales">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        @if(auth()->id() !== $user->id)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger btn-eliminar" 
                                                    data-id="{{ $user->id }}" 
                                                    data-nombre="{{ $user->name }}"
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-2 d-block"></i>
                                    No hay otros usuarios registrados en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Paginación --}}
        @if($usuarios->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal de Confirmación para Eliminar --}}
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                <h5 class="fw-bold mb-1">¿Eliminar usuario?</h5>
                <p class="text-muted small mb-3">Esta acción desactivará el acceso para <strong id="nombreUsuarioModal"></strong>.</p>
                
                <form id="formEliminar" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-3">Sí, eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/usuarios.js') }}"></script>
@endsection

@section('scripts')
    <script>
            <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
    
@endsection