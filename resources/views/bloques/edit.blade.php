@extends('template')

@section('titulo', 'Editar Bloque ' . $bloque->nombre)

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

<h2 class="mb-4 text-primary">Editar Bloque</h2>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Datos del Bloque</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('bloques.update', $bloque) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre del Bloque</label>
                    <input type="text" name="nombre" class="form-control" maxlength="50" value="{{ old('nombre', $bloque->nombre) }}" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Proyecto</label>
                    <input type="hidden" name="lotificacion_id" value="{{ $bloque->lotificacion_id }}">
                    <input type="text" class="form-control" value="{{ $bloque->lotificacion ? $bloque->lotificacion->nombre : 'N/A' }}" disabled>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Descripci&oacute;n (opcional)</label>
                <textarea name="descripcion" class="form-control" rows="3" maxlength="255">{{ old('descripcion', $bloque->descripcion) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="{{ route('bloques.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
