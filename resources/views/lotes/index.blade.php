@extends('template')

@section('titulo', 'Lotes del Bloque ' . $bloque->nombre)

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

<h2 class="mb-1 text-primary">
    Lotes del Bloque {{ $bloque->nombre }}
    <a href="{{ route('bloques.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="fas fa-arrow-left"></i> Volver a Bloques
    </a>
</h2>
<p class="text-muted mb-4">Proyecto: <strong>{{ $bloque->proyecto ?: '—' }}</strong></p>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Lotes Registrados</h6>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearLote">
            <i class="fas fa-plus"></i> Agregar Lote
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>N° Lote</th>
                        <th>Área (m²)</th>
                        <th>Precio Base</th>
                        <th>Estado</th>
                        <th class="text-center" style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lotes as $lote)
                        <tr>
                            <td class="fw-bold">{{ $lote->numero_lote }}</td>
                            <td>{{ number_format($lote->area_metros, 2) }}</td>
                            <td>${{ number_format($lote->precio_base, 2) }}</td>
                            <td>
                                <span class="badge text-white
                                    @if ($lote->estado === 'Disponible') bg-success text-white
                                    @elseif ($lote->estado === 'Reservado') bg-warning text-white
                                    @else bg-secondary
                                    @endif">
                                    {{ $lote->estado }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('lotes.edit', $lote) }}" class="btn btn-sm btn-warning" title="Editar Lote">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Eliminar Lote"
                                        data-bs-toggle="modal" data-bs-target="#modalEliminarLote{{ $lote->id_lote }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Este bloque aún no tiene lotes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- Modal: Crear Lote --}}
{{-- ================================================= --}}
<div class="modal fade" id="modalCrearLote" tabindex="-1" aria-labelledby="modalCrearLoteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('lotes.store', $bloque) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalCrearLoteLabel">Agregar Lote al Bloque {{ $bloque->nombre }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">N° de Lote</label>
                        <input type="text" name="numero_lote" class="form-control" placeholder="Ej: {{ $bloque->nombre }}-01" maxlength="10" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área (vrs²)</label>
                            <input type="number" step="0.01" min="0.01" id="area_varas_create" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área (m²)</label>
                            <input type="number" step="0.01" min="0.01" id="area_metros_create" name="area_metros" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio por vr² (USD)</label>
                            <input type="number" step="0.01" min="0.01" id="precio_vara_create" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio Total Base (USD)</label>
                            <input type="number" step="0.01" min="0.01" id="precio_base_create" name="precio_base" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="Disponible" selected>Disponible</option>
                            <option value="Reservado">Reservado</option>
                            <option value="Vendido">Vendido</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Guardar Lote
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ================================================= --}}
{{-- Modales: Confirmar Eliminación de Lote --}}
{{-- ================================================= --}}
@foreach ($lotes as $lote)
    <div class="modal fade" id="modalEliminarLote{{ $lote->id_lote }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el lote <strong>{{ $lote->numero_lote }}</strong>?</p>
                    <p class="text-muted small mb-0">Esta acción es irreversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('lotes.destroy', $lote) }}" method="POST">
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
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
    
    <script>
    $(document).ready(function() {
        const factor = 1.418415;

        // Convertir m² a vrs²
        $('#area_metros_create').on('input', function() {
            var m2 = parseFloat($(this).val());
            if (!isNaN(m2)) {
                var vrs2 = m2 * factor;
                $('#area_varas_create').val(vrs2.toFixed(2));
                calcularPrecioBaseCreate();
            } else {
                $('#area_varas_create').val('');
            }
        });

        // Convertir vrs² a m²
        $('#area_varas_create').on('input', function() {
            var vrs2 = parseFloat($(this).val());
            if (!isNaN(vrs2)) {
                var m2 = vrs2 / factor;
                $('#area_metros_create').val(m2.toFixed(2));
                calcularPrecioBaseCreate();
            } else {
                $('#area_metros_create').val('');
            }
        });

        // Calcular precio base total a partir del precio por vara y el area en varas
        $('#precio_vara_create').on('input', function() {
            calcularPrecioBaseCreate();
        });

        function calcularPrecioBaseCreate() {
            var area_varas = parseFloat($('#area_varas_create').val());
            var precio_vara = parseFloat($('#precio_vara_create').val());
            if (!isNaN(area_varas) && !isNaN(precio_vara) && area_varas > 0) {
                var precio_total = area_varas * precio_vara;
                $('#precio_base_create').val(precio_total.toFixed(2));
            }
        }
    });
    </script>
@endsection
