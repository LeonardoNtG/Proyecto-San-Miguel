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
                    <label class="form-label">Área (m²)</label>
                    <input type="number" step="0.01" min="0.01" name="area_metros" class="form-control" value="{{ old('area_metros', $lote->area_metros) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Precio Base (USD)</label>
                    <input type="number" step="0.01" min="0.01" name="precio_base" class="form-control" value="{{ old('precio_base', $lote->precio_base) }}" required>
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
    <script>
    <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
    <script src="{{ asset('js/chartM.js') }}"></script>
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
@endsection
