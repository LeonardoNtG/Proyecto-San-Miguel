@extends('template')

@section('titulo', 'Registrar Reserva')

@section('contenido')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-calendar-check text-primary"></i> Registrar Nueva Reserva</h1>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('reservas.store') }}" method="POST">
    @csrf

    {{-- SECCIÓN 1: DATOS PERSONALES DEL CLIENTE --}}
    <div class="card shadow-sm border-left-primary mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user"></i> Datos del Cliente</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-5 mb-3">
                    <label for="nombre_completo" class="form-label font-weight-bold text-secondary">Nombre Completo / Representante</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombres_apellidos" value="{{ old('nombres_apellidos') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cedula" class="form-label font-weight-bold text-secondary">Cédula</label>
                    <input type="text" class="form-control" id="cedula" name="identificacion" value="{{ old('identificacion') }}" placeholder="000-000000-0000A" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="telefono" class="form-label font-weight-bold text-secondary">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="+505 0000-0000">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="estado_civil" class="form-label font-weight-bold text-secondary">Estado Civil</label>
                    <select class="form-select" id="estado_civil" name="estado_civil" required>
                        <option value="">Seleccione...</option>
                        <option value="soltero">Soltero(a)</option>
                        <option value="casado">Casado(a)</option>
                        <option value="union_libre">Unión Libre</option>
                        <option value="divorciado">Divorciado(a)</option>
                        <option value="viudo">Viudo(a)</option>
                    </select>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="direccion" class="form-label font-weight-bold text-secondary">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="{{ old('direccion') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: LOTE Y RESERVA --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-map-marked-alt"></i> Selección de Lotes y Detalles de Reserva</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="proyecto_select" class="form-label font-weight-bold text-secondary">1. Seleccione Proyecto</label>
                    <select class="form-select border-info" id="proyecto_select" name="lotificacion_id" required>
                        <option value="">-- Escoger Proyecto --</option>
                        @foreach ($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}">{{ $proyecto->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="bloque" class="form-label font-weight-bold text-secondary">2. Seleccione Bloque</label>
                    <select class="form-select border-info" id="bloque_select" name="bloque_id" required disabled>
                        <option value="">Seleccione un Proyecto primero</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="lotes_seleccionados" class="form-label font-weight-bold text-secondary">3. Lotes a Reservar</label>
                    <select class="form-select border-info shadow-sm" id="lote_select" name="lotes_ids[]" multiple required disabled style="min-height: 120px;">
                        <option value="">Seleccione un Bloque primero</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6 mb-3">
                    <label for="monto_reserva" class="form-label font-weight-bold text-secondary">Monto de la Reserva (Anticipo)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" class="form-control font-weight-bold text-success" id="monto_reserva" name="monto_reserva" placeholder="0.00" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dias_validez" class="form-label font-weight-bold text-secondary">Días de Validez (Plazo para Formalizar)</label>
                    <div class="input-group">
                        <input type="number" min="1" class="form-control" id="dias_validez" name="dias_validez" value="15" required>
                        <span class="input-group-text">Días</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <a href="{{ route('reservas.index') }}" class="btn btn-secondary btn-lg mr-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5"><i class="fas fa-save"></i> Guardar Reserva</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Evitar que el scroll del mouse cambie el valor de los inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
    });

    $('#proyecto_select').change(function() {
        var proyecto = $(this).val();
        var bloqueSelect = $('#bloque_select');
        var loteSelect = $('#lote_select');

        bloqueSelect.html('<option value="">Cargando bloques...</option>').prop('disabled', true);
        loteSelect.html('<option value="">Seleccione un Bloque primero</option>').prop('disabled', true);

        if (proyecto) {
            var ajaxUrl = '{{ url("api/lotificaciones") }}' + '/' + encodeURIComponent(proyecto) + '/bloques';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    bloqueSelect.html('<option value="">Seleccione Bloque</option>');
                    if (data.length > 0) {
                        $.each(data, function(key, bloque) {
                            bloqueSelect.append('<option value="' + bloque.id_bloque + '">Bloque ' + bloque.nombre + '</option>');
                        });
                        bloqueSelect.prop('disabled', false);
                    } else {
                        bloqueSelect.html('<option value="">No hay bloques en este proyecto</option>');
                    }
                }
            });
        }
    });

    $('#bloque_select').change(function() {
        var bloqueId = $(this).val();
        var loteSelect = $('#lote_select');

        loteSelect.html('<option value="">Cargando lotes...</option>').prop('disabled', true);

        if (bloqueId) {
            var ajaxUrl = '{{ url("api/bloques") }}' + '/' + bloqueId + '/lotes';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    loteSelect.html('<option value="">Seleccione uno o más Lotes</option>');
                    if (data.length > 0) {
                        $.each(data, function(key, lote) {
                            loteSelect.append('<option value="' + lote.id_lote + '">' + lote.numero_lote + '</option>');
                        });
                        loteSelect.prop('disabled', false);
                    } else {
                        loteSelect.html('<option value="">No hay lotes disponibles en este bloque</option>');
                    }
                }
            });
        }
    });
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
