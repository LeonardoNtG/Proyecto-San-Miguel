@extends('template')

@section('titulo', 'Registrar Reserva')

@section('contenido')

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* Estilos extra para embellecer los totales */
    .financial-card {
        border-radius: 10px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .financial-card:hover {
        transform: translateY(-2px);
    }
    .bg-area { background: linear-gradient(45deg, #36b9cc, #2c9faf); }
    .bg-precio { background: linear-gradient(45deg, #1cc88a, #13855c); }
    
    /* Ocultar flechas (spinners) en inputs tipo number */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

@if (session('error'))
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-bookmark text-primary"></i> Registrar Nueva Reserva</h1>
</div>

<form id="form-registro-reserva" action="{{ route('reservas.store') }}" method="POST">
    @csrf

    {{-- SECCIÓN 1: DATOS PERSONALES DEL CLIENTE --}}
    <div class="card shadow-sm border-left-primary mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user"></i> Datos Personales y de Contacto</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-5 mb-3">
                    <label for="nombre_completo" class="form-label font-weight-bold text-secondary">Nombre Completo / Representante <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="nombre_completo" name="nombres_apellidos" value="{{ old('nombres_apellidos') }}" placeholder="Ej: JUAN PÉREZ MORALES" style="text-transform: uppercase;" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cedula" class="form-label font-weight-bold text-secondary">Cédula <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="cedula" name="identificacion" value="{{ old('identificacion') }}" placeholder="000-000000-0000A" maxlength="16" style="text-transform: uppercase;" required>
                    <small class="text-muted"><i class="fas fa-id-card text-primary"></i> Formato: XXX-XXXXXX-XXXXX</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="telefono" class="form-label font-weight-bold text-secondary">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="+505 0000-0000">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="estado_civil" class="form-label font-weight-bold text-secondary">Estado Civil <span class="text-danger">*</span></label>
                    <select class="custom-select form-control text-uppercase" id="estado_civil" name="estado_civil" style="text-transform: uppercase;" required>
                        <option value="">Seleccione...</option>
                        <option value="SOLTERO" @selected(strtoupper(old('estado_civil')) == 'SOLTERO')>Soltero(a)</option>
                        <option value="CASADO" @selected(strtoupper(old('estado_civil')) == 'CASADO')>Casado(a)</option>
                        <option value="UNION_DE_HECHO" @selected(strtoupper(old('estado_civil')) == 'UNION_DE_HECHO')>Unión de Hecho</option>
                        <option value="DIVORCIADO" @selected(strtoupper(old('estado_civil')) == 'DIVORCIADO')>Divorciado(a)</option>
                        <option value="VIUDO" @selected(strtoupper(old('estado_civil')) == 'VIUDO')>Viudo(a)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="profesion_oficio" class="form-label font-weight-bold text-secondary">Profesión u Oficio</label>
                    <input type="text" class="form-control text-uppercase" id="profesion_oficio" name="profesion_oficio" value="{{ old('profesion_oficio') }}" placeholder="Ej: Comerciante, Docente" style="text-transform: uppercase;">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="domicilio" class="form-label font-weight-bold text-secondary">Domicilio / Municipio</label>
                    <input type="text" class="form-control text-uppercase" id="domicilio" name="domicilio" value="{{ old('domicilio') }}" placeholder="Ej: San Miguel, San Rafael del Sur" style="text-transform: uppercase;">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-12 mb-3">
                    <label for="direccion" class="form-label font-weight-bold text-secondary">Dirección Exacta</label>
                    <textarea class="form-control text-uppercase" id="direccion" name="direccion" rows="1" placeholder="Dirección domiciliar del cliente..." style="text-transform: uppercase;">{{ old('direccion') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: ASIGNACIÓN DE TERRENO / LOTES --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-layer-group"></i> Asignación de Terreno / Lotes</h6>
            <span class="badge bg-primary text-white px-3 py-2 fs-6">
                <i class="fas fa-building me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
            </span>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <!-- Proyecto Bloqueado -->
                <div class="col-md-4 mb-3">
                    <label class="form-label font-weight-bold text-secondary">
                        <i class="fas fa-building text-primary"></i> Proyecto Activo
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="fas fa-lock"></i></span>
                        <input type="text" class="form-control bg-white fw-bold text-primary" value="{{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}" readonly>
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Para reservar en otro proyecto, cámbielo en la barra superior.</small>
                    <input type="hidden" id="proyecto_select" name="lotificacion_id" value="{{ $lotificacionActiva->id ?? session('lotificacion_id') }}">
                </div>

                <!-- Bloque -->
                <div class="col-md-4 mb-3">
                    <label for="bloque_select" class="form-label font-weight-bold text-secondary">Bloque / Manzana <span class="text-danger">*</span></label>
                    <select class="custom-select form-control" id="bloque_select" name="id_bloque" required>
                        <option value="">-- Seleccionar Bloque --</option>
                        @isset($bloques)
                            @foreach ($bloques as $bloque)
                                <option value="{{ $bloque->id_bloque }}" @selected(old('id_bloque') == $bloque->id_bloque)>
                                    {{ $bloque->nombre }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>

                <!-- Lotes (Múltiple) -->
                <div class="col-md-4 mb-3">
                    <label for="lote_select" class="form-label font-weight-bold text-secondary">Lote(s) a Reservar <span class="text-danger">*</span></label>
                    <select class="custom-select form-control" id="lote_select" name="lotes_ids[]" multiple="multiple" required disabled>
                    </select>
                    <small class="text-muted">Puede seleccionar múltiples lotes si la reserva abarca más de un lote.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 3: CONDICIONES DE LA RESERVA Y ANTICIPO --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-hand-holding-usd"></i> Condiciones Financieras y Anticipo de Reserva</h6>
        </div>
        <div class="card-body bg-light">
            <!-- Tarjetas de Totales -->
            <div class="row g-3 mb-4">
                <!-- Extensión -->
                <div class="col-md-6">
                    <div class="financial-card bg-area d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Extensión TOTAL</h6>
                            <h3 class="mb-0 fw-bold" id="display_extension">0.00 <small class="fs-6">vrs²</small></h3>
                            <input type="hidden" id="extension_lote_value" name="extension_value">
                        </div>
                        <i class="fas fa-ruler-combined fa-3x opacity-50"></i>
                    </div>
                </div>
                <!-- Precio Estimado -->
                <div class="col-md-6">
                    <div class="financial-card bg-precio d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Valor del Terreno (Base)</h6>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 fw-bold me-2">$</h3>
                                <input type="number" step="0.01" min="0" class="form-control bg-transparent text-white border-0 shadow-none fw-bold p-0" style="font-size: 1.4rem;" id="monto_lote" placeholder="0.00" readonly>
                            </div>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>

            <hr class="mb-4">

            <!-- Inputs de Anticipo y Días -->
            <div class="row g-3 mb-3">
                <div class="col-md-4 mb-3">
                    <label for="monto_reserva" class="form-label font-weight-bold text-secondary"><i class="fas fa-money-bill-wave text-success"></i> Monto de Reserva (Anticipo)</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-success text-white border-success">$</span>
                        <input type="number" step="0.01" min="0" class="form-control border-success font-weight-bold text-success" id="monto_reserva" name="monto_reserva" placeholder="0.00" value="{{ old('monto_reserva', '0.00') }}">
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle text-info"></i> Opcional (dejar en $0.00 si no requiere pago).</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="dias_validez" class="form-label font-weight-bold text-secondary"><i class="fas fa-hourglass-half text-warning"></i> Días de Validez (Plazo Formalizar) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg shadow-sm">
                        <input type="number" min="1" max="90" class="form-control font-weight-bold" id="dias_validez" name="dias_validez" value="{{ old('dias_validez', 5) }}" required>
                        <span class="input-group-text bg-white">Días</span>
                    </div>
                    <small class="text-muted"><i class="fas fa-calendar-check text-primary"></i> Plazo por defecto: 5 días.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="fecha_reserva" class="form-label font-weight-bold text-secondary"><i class="fas fa-calendar-alt text-primary"></i> Fecha de Registro</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar text-muted"></i></span>
                        <input type="date" class="form-control border-start-0 ps-0" id="fecha_reserva" name="fecha_reserva" value="{{ old('fecha_reserva', now()->format('Y-m-d')) }}" readonly>
                    </div>
                </div>
            </div>

            <!-- Datos de pago del Anticipo con Selector Rápido de 1 Clic -->
            <div class="row g-3 bg-white p-3 mb-3 rounded border shadow-sm" id="seccion_pago_anticipo">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold text-secondary d-block">
                        <i class="fas fa-wallet text-primary me-1"></i> Método de Pago (Anticipo)
                    </label>
                    <div class="btn-group w-100 d-flex flex-wrap shadow-sm" role="group" id="group_metodo_pago">
                        <input type="radio" class="btn-check" name="metodo_pago" id="metodo_sin_anticipo" value="Sin Anticipo" autocomplete="off" {{ old('metodo_pago', 'Sin Anticipo') == 'Sin Anticipo' ? 'checked' : '' }} onchange="toggleMetodoFields()">
                        <label class="btn btn-outline-dark py-2 fw-bold flex-fill" for="metodo_sin_anticipo">
                            <i class="fas fa-ban me-1"></i> Sin Anticipo ($0)
                        </label>

                        <input type="radio" class="btn-check" name="metodo_pago" id="metodo_efectivo" value="Efectivo" autocomplete="off" {{ old('metodo_pago') == 'Efectivo' ? 'checked' : '' }} onchange="toggleMetodoFields()">
                        <label class="btn btn-outline-success py-2 fw-bold flex-fill" for="metodo_efectivo">
                            <i class="fas fa-money-bill-wave me-1"></i> Efectivo
                        </label>

                        <input type="radio" class="btn-check" name="metodo_pago" id="metodo_transferencia" value="Transferencia Bancaria" autocomplete="off" {{ old('metodo_pago') == 'Transferencia Bancaria' ? 'checked' : '' }} onchange="toggleMetodoFields()">
                        <label class="btn btn-outline-primary py-2 fw-bold flex-fill" for="metodo_transferencia">
                            <i class="fas fa-exchange-alt me-1"></i> Transferencia
                        </label>

                        <input type="radio" class="btn-check" name="metodo_pago" id="metodo_deposito" value="Depósito Bancario" autocomplete="off" {{ old('metodo_pago') == 'Depósito Bancario' ? 'checked' : '' }} onchange="toggleMetodoFields()">
                        <label class="btn btn-outline-info py-2 fw-bold flex-fill" for="metodo_deposito">
                            <i class="fas fa-university me-1"></i> Depósito
                        </label>
                    </div>
                </div>
                <div class="col-md-3 mb-3" id="div_cuenta" style="display: none;">
                    <label for="cuenta_destino" class="form-label font-weight-bold text-secondary">Cuenta Destino</label>
                    <input type="text" class="form-control" id="cuenta_destino" name="cuenta_destino" placeholder="Ej: BANPRO - Empresa">
                </div>
                <div class="col-md-3 mb-3" id="div_referencia">
                    <label for="referencia" class="form-label font-weight-bold text-secondary" id="label_referencia">Referencia / Comentarios</label>
                    <input type="text" class="form-control" id="referencia" name="referencia" placeholder="Registro de Reserva">
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
                            <span class="small text-muted d-block" id="modal-res-cedula">Cédula: -</span>
                            <span class="small text-muted d-block" id="modal-res-telefono">Tel: -</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Proyecto y Bloque</span>
                            <span class="badge bg-primary text-white fs-6 mb-1 px-3 py-1">
                                <i class="fas fa-building me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
                            </span>
                            <div class="text-dark mt-1">
                                Bloque: <strong class="text-primary fw-bold" id="modal-res-bloque">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small text-uppercase fw-bold">Lote(s) a Reservar</span>
                        <span class="text-info fw-bold small" id="modal-res-extension">-</span>
                    </div>
                    <div id="modal-res-lotes-container">
                        <!-- Badges -->
                    </div>
                </div>

                <div class="card border-primary mb-2">
                    <div class="card-body p-3 bg-light">
                        <div class="row text-center align-items-center">
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block">Monto Anticipo</span>
                                <span class="fs-4 fw-bold text-success" id="modal-res-monto">$0.00</span>
                            </div>
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block">Plazo de Validez</span>
                                <span class="fs-5 fw-bold text-dark" id="modal-res-dias">15 Días</span>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small d-block">Método de Pago</span>
                                <span class="fs-6 fw-bold text-primary" id="modal-res-metodo">Efectivo</span>
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
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function toggleMetodoFields() {
    var metodo = $('input[name="metodo_pago"]:checked').val() || 'Sin Anticipo';
    if (metodo === 'Efectivo' || metodo === 'Sin Anticipo') {
        $('#div_cuenta').hide();
        $('#div_referencia').removeClass('col-md-3').addClass('col-md-6');
        $('#label_referencia').text('Referencia / Comentarios');
        $('#referencia').attr('placeholder', 'Registro de Reserva');
    } else {
        $('#div_cuenta').show();
        $('#div_referencia').removeClass('col-md-6').addClass('col-md-3');
        $('#label_referencia').html('N° Referencia / Transferencia <span class="text-danger">*</span>');
        $('#referencia').attr('placeholder', 'Ej: TR-8945201');
    }
}

$(document).ready(function() {
    toggleMetodoFields();

    // Auto-ajustar método de pago al escribir monto
    $('#monto_reserva').on('input', function() {
        var val = parseFloat($(this).val()) || 0;
        var current = $('input[name="metodo_pago"]:checked').val();
        if (val > 0 && current === 'Sin Anticipo') {
            $('#metodo_efectivo').prop('checked', true).trigger('change');
        } else if (val <= 0 && current !== 'Sin Anticipo') {
            $('#metodo_sin_anticipo').prop('checked', true).trigger('change');
        }
    });

    // Evitar scroll en inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
    });

    $('#bloque_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione Bloque'
    });

    $('#lote_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione un Bloque primero'
    });

    $('#bloque_select').change(function() {
        var bloqueId = $(this).val();
        var loteSelect = $('#lote_select');

        loteSelect.html('<option value=""></option>').prop('disabled', true);
        loteSelect.select2('destroy').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Cargando lotes...' });
        $('#extension_lote_value').val('');
        $('#monto_lote').val('');
        $('#display_extension').html('0.00 <small class="fs-6">vrs²</small>');

        if (bloqueId) {
            var ajaxUrl = '{{ url("api/bloques") }}' + '/' + bloqueId + '/lotes';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    loteSelect.html('<option value=""></option>');

                    if (data.length > 0) {
                        $.each(data, function(key, lote) {
                            loteSelect.append('<option value="' + lote.id_lote + '" data-extension="' + lote.area_metros + '" data-precio="' + lote.precio_base + '">' + lote.numero_lote + '</option>');
                        });
                        loteSelect.prop('disabled', false);
                        loteSelect.select2('destroy').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Seleccione uno o más Lotes' });
                    } else {
                        loteSelect.prop('disabled', true);
                        loteSelect.select2('destroy').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'No hay lotes disponibles' });
                    }
                },
                error: function(xhr, status, error) {
                    loteSelect.html('<option value=""></option>').prop('disabled', true);
                    loteSelect.select2('destroy').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Error al cargar lotes' });
                    console.error("AJAX Error:", error, status, xhr.responseText);
                }
            });
        }
    });

    $('#lote_select').change(function() {
        var totalExtensionMetros = 0;
        var totalMonto = 0;

        $(this).find('option:selected').each(function() {
            var extension = parseFloat($(this).data('extension'));
            var precio = parseFloat($(this).data('precio'));
            if (!isNaN(extension)) {
                totalExtensionMetros += extension;
            }
            if (!isNaN(precio)) {
                totalMonto += precio;
            }
        });

        // Convertir m² a vrs² para mostrar en UI
        var factorVara = 1.418415;
        var totalExtensionVaras = totalExtensionMetros * factorVara;

        if (Math.abs(Math.round(totalExtensionVaras) - totalExtensionVaras) < 0.02) {
            totalExtensionVaras = Math.round(totalExtensionVaras);
        }

        $('#extension_lote_value').val(totalExtensionMetros.toFixed(2));
        $('#display_extension').html(totalExtensionVaras.toFixed(2) + ' <small class="fs-6">vrs²</small> <span class="fs-6 font-weight-normal text-white-50 ms-2">(' + totalExtensionMetros.toFixed(2) + ' m²)</span>');
        $('#monto_lote').val(totalMonto.toFixed(2));
        
        // Feedback visual
        $('.financial-card').css('transform', 'scale(1.02)');
        setTimeout(function() {
            $('.financial-card').css('transform', 'scale(1)');
        }, 200);
    });

    // Modal de Confirmación de Reserva
    var modalReservaEl = document.getElementById('modalConfirmarReserva');
    var modalReserva = new bootstrap.Modal(modalReservaEl);
    var formReserva = document.getElementById('form-registro-reserva');

    $('#btn-preparar-reserva').on('click', function(e) {
        e.preventDefault();

        if (!formReserva.checkValidity()) {
            formReserva.reportValidity();
            return;
        }

        var lotesSeleccionados = $('#lote_select').val();
        if (!lotesSeleccionados || lotesSeleccionados.length === 0 || lotesSeleccionados[0] === "") {
            alert('Debe seleccionar al menos un lote para la reserva.');
            $('#lote_select').select2('open');
            return;
        }

        var clienteNombre = $('#nombre_completo').val();
        var cedula = $('#cedula').val();
        var telefono = $('#telefono').val() || 'No especificado';
        $('#modal-res-cliente').text(clienteNombre);
        $('#modal-res-cedula').text('Cédula: ' + cedula);
        $('#modal-res-telefono').text('Tel: ' + telefono);

        var bloqueTexto = $('#bloque_select option:selected').text();
        $('#modal-res-bloque').text(bloqueTexto.trim());

        var containerLotes = $('#modal-res-lotes-container');
        containerLotes.empty();
        $('#lote_select option:selected').each(function() {
            if ($(this).val()) {
                containerLotes.append('<span class="badge bg-dark text-white me-1 mb-1 fs-6 px-3 py-2">Lote ' + $(this).text() + '</span>');
            }
        });

        var extensionTxt = $('#display_extension').text();
        $('#modal-res-extension').text('Extensión: ' + extensionTxt);

        var montoVal = parseFloat($('#monto_reserva').val()) || 0;
        var diasVal = parseInt($('#dias_validez').val()) || 5;
        var metodo = $('input[name="metodo_pago"]:checked').val() || 'Sin Anticipo';

        if (montoVal <= 0) {
            $('#modal-res-monto').text('$0.00 (Sin Anticipo)');
            $('#modal-res-metodo').text('Sin Anticipo');
        } else {
            $('#modal-res-monto').text('$' + montoVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#modal-res-metodo').text(metodo);
        }
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

    // Formateador automático de Cédula (XXX-XXXXXX-XXXXX)
    function formatearCedula(input) {
        let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (val.length > 14) val = val.substring(0, 14);

        let formatted = '';
        if (val.length > 0) {
            formatted += val.substring(0, Math.min(3, val.length));
        }
        if (val.length > 3) {
            formatted += '-' + val.substring(3, Math.min(9, val.length));
        }
        if (val.length > 9) {
            formatted += '-' + val.substring(9, 14);
        }
        input.value = formatted;
    }

    const cedulaInput = document.getElementById('cedula');
    if (cedulaInput) {
        cedulaInput.addEventListener('input', function() {
            formatearCedula(this);
        });
        cedulaInput.addEventListener('blur', function() {
            formatearCedula(this);
        });
    }

    document.querySelectorAll('.text-uppercase').forEach(function(el) {
        el.addEventListener('input', function() {
            if (this.id !== 'cedula') {
                this.value = this.value.toUpperCase();
            }
        });
    });
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
