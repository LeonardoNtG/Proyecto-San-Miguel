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
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-map-marked-alt"></i> Selección de Lotes y Detalles de Reserva</h6>
            <span class="badge bg-primary text-white px-3 py-2 fs-6">
                <i class="fas fa-project-diagram me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
            </span>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label font-weight-bold text-secondary">1. Proyecto / Lotificación</label>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="fas fa-map-marked-alt"></i></span>
                        <input type="text" class="form-control font-weight-bold bg-white" value="{{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}" readonly>
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Para reservar en otro proyecto, cámbielo en el menú superior.</small>
                    <input type="hidden" id="proyecto_select" name="lotificacion_id" value="{{ $lotificacionActiva->id ?? session('lotificacion_id') }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label for="bloque_select" class="form-label font-weight-bold text-secondary">2. Seleccione Bloque</label>
                    <select class="custom-select form-control border-info" id="bloque_select" name="bloque_id" required>
                        <option value="">-- Seleccione Bloque --</option>
                        @isset($bloques)
                            @foreach ($bloques as $bloque)
                                <option value="{{ $bloque->id_bloque }}">Bloque {{ $bloque->nombre }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="lotes_seleccionados" class="form-label font-weight-bold text-secondary">3. Lotes a Reservar</label>
                    <select class="custom-select form-control border-info shadow-sm" id="lote_select" name="lotes_ids[]" multiple required disabled style="min-height: 120px;">
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
        <a href="{{ route('reservas.index') }}" class="btn btn-secondary btn-lg me-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="button" id="btn-preparar-reserva" class="btn btn-primary btn-lg shadow-sm px-5 py-2">
            <i class="fas fa-check-circle me-1"></i> Revisar y Guardar Reserva
        </button>
    </div>
</form>

{{-- ================================================= --}}
{{-- MODAL DE RESUMEN Y CONFIRMACIÓN DE RESERVA --}}
{{-- ================================================= --}}
<div class="modal fade" id="modalConfirmarReserva" tabindex="-1" aria-labelledby="modalConfirmarReservaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalConfirmarReservaLabel">
                    <i class="fas fa-bookmark me-2"></i> Resumen y Confirmación de Reserva
                </h5>
                <button type="button" class="close text-white" id="btn-x-modal-reserva" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center border-info">
                    <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                    <div>
                        <strong class="text-dark">Confirmación de Reserva de Lotes:</strong>
                        <div class="small text-secondary">Los lotes seleccionados quedarán bloqueados temporalmente bajo el nombre del cliente por el plazo establecido.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Cliente Seleccionado</span>
                            <span class="fs-5 fw-bold text-dark d-block" id="modal-res-cliente">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Proyecto y Bloque</span>
                            <span class="badge bg-primary text-white fs-6 mb-1 px-3 py-1">
                                <i class="fas fa-map-marker-alt me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
                            </span>
                            <div class="text-dark mt-1">
                                Bloque: <strong class="text-primary fw-bold" id="modal-res-bloque">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border mb-3">
                    <span class="text-secondary d-block small text-uppercase fw-bold mb-2">Lote(s) a Reservar</span>
                    <div id="modal-res-lotes-container">
                        <!-- Badges -->
                    </div>
                </div>

                <div class="card border-primary mb-2">
                    <div class="card-body p-3 bg-light">
                        <div class="row text-center align-items-center">
                            <div class="col-6 border-end">
                                <span class="text-muted small d-block">Monto de Reserva / Anticipo</span>
                                <span class="fs-4 fw-bold text-success" id="modal-res-monto">$0.00</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Plazo de Validez</span>
                                <span class="fs-5 fw-bold text-dark" id="modal-res-dias">15 Días</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 border-top">
                <button type="button" class="btn btn-secondary text-white px-4 fw-bold" id="btn-cancelar-modal-reserva" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="fas fa-edit me-1"></i> Modificar Datos
                </button>
                <button type="button" id="btn-confirmar-guardar-reserva" class="btn btn-primary text-white px-4 fw-bold shadow-sm">
                    <i class="fas fa-check-circle me-1"></i> Confirmar y Guardar Reserva
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Evitar que el scroll del mouse cambie el valor de los inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
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
                        loteSelect.html('<option value="">No hay lotes disponibles (vendidos o reservados)</option>');
                    }
                }
            });
        }
    });

    // Disparar el evento change si hay un bloque preseleccionado al cargar la página
    if ($('#bloque_select').val()) {
        $('#bloque_select').trigger('change');
    }

    // Modal de Confirmación de Reserva
    var modalReservaEl = document.getElementById('modalConfirmarReserva');
    var modalReserva = new bootstrap.Modal(modalReservaEl);
    var formReserva = $('form')[0];

    $('#btn-preparar-reserva').on('click', function(e) {
        e.preventDefault();

        if (!formReserva.checkValidity()) {
            formReserva.reportValidity();
            return;
        }

        var lotesSeleccionados = $('#lote_select').val();
        if (!lotesSeleccionados || lotesSeleccionados.length === 0 || lotesSeleccionados[0] === "") {
            alert('Debe seleccionar al menos un lote para la reserva.');
            $('#lote_select').focus();
            return;
        }

        var clienteTexto = $('#id_cliente option:selected').text();
        $('#modal-res-cliente').text(clienteTexto.trim());

        var bloqueTexto = $('#bloque_select option:selected').text();
        $('#modal-res-bloque').text(bloqueTexto.trim());

        var containerLotes = $('#modal-res-lotes-container');
        containerLotes.empty();
        $('#lote_select option:selected').each(function() {
            if ($(this).val()) {
                containerLotes.append('<span class="badge bg-dark text-white me-1 mb-1">Lote ' + $(this).text() + '</span>');
            }
        });

        var montoVal = parseFloat($('#monto_reserva').val()) || 0;
        var diasVal = parseInt($('#dias_validez').val()) || 15;

        $('#modal-res-monto').text('$' + montoVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#modal-res-dias').text(diasVal + ' Días de Validez');

        modalReserva.show();
    });

    // Cerrar modal al hacer clic en "Modificar Datos" o en "X"
    $('#btn-cancelar-modal-reserva, #btn-x-modal-reserva').on('click', function(e) {
        e.preventDefault();
        try { modalReserva.hide(); } catch(err) {}
        $('#modalConfirmarReserva').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    $('#btn-confirmar-guardar-reserva').on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando Reserva...');
        formReserva.submit();
    });
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
