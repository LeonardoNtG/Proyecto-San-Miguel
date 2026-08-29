@extends('template')

@section('titulo', 'Bloques y Lotes')

@section('contenido')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Revisa los siguientes datos:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="m-0 font-weight-bold text-primary d-inline-block">Bloques Registrados</h5>
            <span class="badge bg-primary text-white ms-2 px-3 py-2">
                <i class="fas fa-project-diagram me-1"></i> {{ $userLotificaciones->firstWhere('id', $activeLotificacionId)->nombre ?? 'Proyecto Activo' }}
            </span>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearBloque">
            <i class="fas fa-plus"></i> Agregar Bloque
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <form action="{{ route('bloques.index') }}" method="GET" class="d-flex flex-wrap align-items-center">
                    <div class="me-2 mb-2" style="min-width: 280px;">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por Nombre o Descripción..." value="{{ request('search') }}">
                    </div>
                    <div class="mb-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                        @if(request('search'))
                            <a href="{{ route('bloques.index') }}" class="btn btn-secondary ms-2" title="Limpiar búsqueda"><i class="fas fa-times"></i> Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Bloque</th>
                        <th>Proyecto</th>
                        <th>Descripción</th>
                        <th class="text-center">N° de Lotes</th>
                        <th class="text-center" style="width: 260px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bloques as $bloque)
                        <tr>
                            <td class="fw-bold">{{ $bloque->nombre }}</td>
                            <td>{{ $bloque->lotificacion ? $bloque->lotificacion->nombre : '—' }}</td>
                            <td>{{ $bloque->descripcion ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info text-white">{{ $bloque->lotes_count }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('lotes.index', $bloque) }}" class="btn btn-sm btn-success" title="Ver / Añadir Lotes">
                                    <i class="fas fa-map-marker-alt"></i> Lotes
                                </a>
                                <a href="{{ route('bloques.edit', $bloque) }}" class="btn btn-sm btn-warning" title="Editar Bloque">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Eliminar Bloque"
                                        data-bs-toggle="modal" data-bs-target="#modalEliminarBloque{{ $bloque->id_bloque }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Aún no hay bloques registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-4">
            {{ $bloques->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- Modal: Crear Bloque --}}
{{-- ================================================= --}}
<div class="modal fade" id="modalCrearBloque" tabindex="-1" aria-labelledby="modalCrearBloqueLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('bloques.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCrearBloqueLabel">Agregar Bloque</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Bloque</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: A" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proyecto</label>
                        <input type="hidden" name="lotificacion_id" value="{{ $activeLotificacionId }}">
                        <input type="text" class="form-control" value="{{ $userLotificaciones->firstWhere('id', $activeLotificacionId)->nombre ?? 'N/A' }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea name="descripcion" class="form-control" rows="3" maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Bloque
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ================================================= --}}
{{-- Modales: Confirmar Eliminación de Bloque --}}
{{-- ================================================= --}}
@foreach ($bloques as $bloque)
    <div class="modal fade" id="modalEliminarBloque{{ $bloque->id_bloque }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el bloque <strong>{{ $bloque->nombre }}</strong>?</p>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Este bloque tiene <strong>{{ $bloque->lotes_count }}</strong> lote(s) asociado(s). Si se elimina el bloque, se eliminarán también <strong>todos los lotes relacionados</strong>. Esta acción es irreversible.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('bloques.destroy', $bloque) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Sí, eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

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
