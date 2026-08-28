@extends('template') 

@section('titulo', 'Editar Lotificación') 

@section('contenido') 
<div class="container-fluid px-4 py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Configurar Proyecto: {{ $lotificacion->nombre }}</h3>
            <p class="text-muted small mb-0">Personaliza los datos que aparecerán en los recibos impresos de este proyecto.</p>
        </div>
        <a href="{{ route('lotificaciones.index') }}" class="btn btn-secondary px-3 shadow-sm rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0 col-md-8 mx-auto">
        <div class="card-body p-4">
            <form action="{{ route('lotificaciones.update', $lotificacion->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del Proyecto</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $lotificacion->nombre) }}" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">RUC</label>
                        <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $lotificacion->ruc) }}" placeholder="Ej: 3231411710002L">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Teléfono de Contacto</label>
                        <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $lotificacion->telefono) }}" placeholder="Ej: 8930 4712">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Ciudad</label>
                    <input type="text" name="ciudad" class="form-control" value="{{ old('ciudad', $lotificacion->ciudad) }}" placeholder="Ej: ESTELÍ">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Logo del Proyecto (Opcional)</label>
                    @if($lotificacion->logo)
                        <div class="mb-2">
                            <img src="{{ asset('storage/'.$lotificacion->logo) }}" alt="Logo Actual" style="height: 60px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                            <p class="small text-muted mt-1">Logo actual</p>
                        </div>
                    @endif
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-muted">Formatos recomendados: PNG o JPG. Tamaño ideal: 200x200 px.</small>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Guardar Configuración</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
