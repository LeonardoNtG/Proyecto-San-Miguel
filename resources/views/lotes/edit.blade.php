@extends('template')

@section('titulo', 'Editar Lote ' . $lote->numero_lote)

@section('contenido')

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

<h2 class="mb-4 text-primary">
    Editar Lote {{ $lote->numero_lote }}
    <small class="text-muted fs-6 ms-2">Bloque {{ $bloque->nombre }} &middot; {{ $bloque->proyecto ?: '—' }}</small>
</h2>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Datos del Lote</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('lotes.update', $lote) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">N° de Lote</label>
                    <input type="text" name="numero_lote" class="form-control" maxlength="10" value="{{ old('numero_lote', $lote->numero_lote) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Área (vrs²)</label>
                    <input type="number" step="0.01" min="0.01" id="area_varas_edit" class="form-control" value="{{ old('area_metros', $lote->area_metros) * 1.418415 }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Área (m²)</label>
                    <input type="number" step="0.01" min="0.01" id="area_metros_edit" name="area_metros" class="form-control" value="{{ old('area_metros', $lote->area_metros) }}" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio por vr² (USD)</label>
                    <input type="number" step="0.01" min="0.01" id="precio_vara_edit" class="form-control" value="{{ old('area_metros', $lote->area_metros) > 0 ? old('precio_base', $lote->precio_base) / (old('area_metros', $lote->area_metros) * 1.418415) : '' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio Total Base (USD)</label>
                    <input type="number" step="0.01" min="0.01" id="precio_base_edit" name="precio_base" class="form-control" value="{{ old('precio_base', $lote->precio_base) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        @foreach (['Disponible', 'Reservado', 'Vendido'] as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $lote->estado) === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="{{ route('lotes.index', $bloque) }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <script>
    $(document).ready(function() {
        const factor = 1.418415;

        // Convertir m² a vrs²
        $('#area_metros_edit').on('input', function() {
            var m2 = parseFloat($(this).val());
            if (!isNaN(m2)) {
                var vrs2 = m2 * factor;
                $('#area_varas_edit').val(vrs2.toFixed(2));
                calcularPrecioBaseEdit();
            } else {
                $('#area_varas_edit').val('');
            }
        });

        // Convertir vrs² a m²
        $('#area_varas_edit').on('input', function() {
            var vrs2 = parseFloat($(this).val());
            if (!isNaN(vrs2)) {
                var m2 = vrs2 / factor;
                $('#area_metros_edit').val(m2.toFixed(2));
                calcularPrecioBaseEdit();
            } else {
                $('#area_metros_edit').val('');
            }
        });

        // Calcular precio base total a partir del precio por vara y el area en varas
        $('#precio_vara_edit').on('input', function() {
            calcularPrecioBaseEdit();
        });

        function calcularPrecioBaseEdit() {
            var area_varas = parseFloat($('#area_varas_edit').val());
            var precio_vara = parseFloat($('#precio_vara_edit').val());
            if (!isNaN(area_varas) && !isNaN(precio_vara) && area_varas > 0) {
                var precio_total = area_varas * precio_vara;
                $('#precio_base_edit').val(precio_total.toFixed(2));
            }
        }
    });
    </script>
@endsection
