@extends('template')

@section('titulo', 'Formalizar Reserva')

@section('contenido')
<style>
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
    
    /* Ocultar flechas en inputs number */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }

    .row-unselected {
        background-color: #f8f9fa !important;
        opacity: 0.65;
    }
</style>

@php
    $extensionTotal = $reserva->lotes->sum('area_metros');
    $precioBaseTotal = $reserva->lotes->sum('precio_base');
    $factorVara = 1.418415;
    $extensionTotalVaras = $extensionTotal * $factorVara;
    if (abs(round($extensionTotalVaras) - $extensionTotalVaras) < 0.02) {
        $extensionTotalVaras = round($extensionTotalVaras);
    }
@endphp

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-signature text-success"></i> Formalizar Reserva #{{ $reserva->id_reserva }}</h1>
</div>

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="form-formalizar" action="{{ route('reservas.procesarFormalizacion', $reserva->id_reserva) }}" method="POST">
    @csrf

    {{-- SECCIÓN 1: RESUMEN DE LA RESERVA Y SELECCIÓN DE LOTES --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-info-circle"></i> Resumen de la Reserva y Selección de Lotes</h6>
            <span class="badge bg-primary text-white px-3 py-2 fs-6">
                <i class="fas fa-building me-1"></i> {{ $reserva->lotificacion->nombre ?? 'Proyecto' }}
            </span>
        </div>
        <div class="card-body bg-light">
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Cliente:</span>
                    <strong class="text-dark fs-5">{{ $reserva->cliente->nombres_apellidos }}</strong> <br>
                    <small class="text-muted"><i class="fas fa-id-card me-1"></i> Cédula: {{ $reserva->cliente->identificacion }}</small>
                </div>
                <div class="col-md-4 mb-3">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Proyecto:</span>
                    <strong class="text-primary fs-5">{{ $reserva->lotificacion->nombre }}</strong> <br>
                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Fecha Reserva: {{ \Carbon\Carbon::parse($reserva->fecha_reserva)->format('d/m/Y') }}</small>
                </div>
                <div class="col-md-4 mb-3">
                    <span class="text-secondary small text-uppercase fw-bold d-block">Monto de Anticipo Abonado:</span>
                    <h4 class="text-success font-weight-bold mb-0">${{ number_format($reserva->monto_reserva, 2) }}</h4>
                    <small class="text-muted">Se abonará como parte de la prima del contrato.</small>
                </div>
            </div>

            <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center border-warning">
                <i class="fas fa-hand-pointer fa-2x me-3 text-warning"></i>
                <div class="small">
                    <strong>Selección de Lotes a Formalizar:</strong>
                    Marque las casillas de los lotes que el cliente comprará en este contrato. 
                    Los lotes que <span class="text-danger fw-bold">desmarque</span> serán <strong>liberados automáticamente</strong> al inventario disponible.
                </div>
            </div>

            <div class="table-responsive bg-white rounded border">
                <table class="table table-hover align-middle mb-0" id="tabla-lotes-reserva">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="check-todos-lotes" checked title="Seleccionar/Deseleccionar todos">
                            </th>
                            <th>Bloque</th>
                            <th>Nº Lote</th>
                            <th>Extensión (vrs²)</th>
                            <th>Precio Base ($)</th>
                            <th>Estado para el Contrato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reserva->lotes as $lote)
                        @php
                            $vrsLote = $lote->area_metros * $factorVara;
                            if (abs(round($vrsLote) - $vrsLote) < 0.02) {
                                $vrsLote = round($vrsLote);
                            }
                        @endphp
                        <tr id="fila_lote_{{ $lote->id_lote }}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input check-lote" 
                                       name="lotes_a_formalizar[]" 
                                       value="{{ $lote->id_lote }}" 
                                       data-area="{{ $lote->area_metros }}" 
                                       data-precio="{{ $lote->precio_base }}" 
                                       data-lote-num="{{ $lote->numero_lote }}"
                                       data-bloque="{{ $lote->bloque ? $lote->bloque->nombre : 'N/D' }}"
                                       checked 
                                       id="chk_{{ $lote->id_lote }}">
                            </td>
                            <td><strong>Bloque {{ $lote->bloque ? $lote->bloque->nombre : 'N/D' }}</strong></td>
                            <td>
                                <label for="chk_{{ $lote->id_lote }}" class="mb-0" style="cursor: pointer;">
                                    <span class="badge bg-dark text-white fs-6 px-3 py-1">Lote {{ $lote->numero_lote }}</span>
                                </label>
                            </td>
                            <td>{{ number_format($vrsLote, 2) }} vrs² <span class="text-muted small">({{ number_format($lote->area_metros, 2) }} m²)</span></td>
                            <td class="font-weight-bold text-success">${{ number_format($lote->precio_base, 2) }}</td>
                            <td>
                                <span class="badge bg-success text-white px-3 py-2 badge-estado-lote" id="badge_lote_{{ $lote->id_lote }}">
                                    <i class="fas fa-check me-1"></i> Incluir en Venta
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: DETALLES FINANCIEROS DE LA VENTA --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-money-check-alt"></i> Datos Financieros del Contrato</h6>
        </div>
        <div class="card-body bg-light">
            <!-- Tarjetas de Totales -->
            <div class="row g-3 mb-4">
                <!-- Extensión -->
                <div class="col-md-6">
                    <div class="financial-card bg-area d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Extensión TOTAL (Lotes Seleccionados)</h6>
                            <h3 class="mb-0 fw-bold" id="display_extension">0.00 <small class="fs-6">vrs²</small></h3>
                        </div>
                        <i class="fas fa-ruler-combined fa-3x opacity-50"></i>
                    </div>
                </div>
                <!-- Precio -->
                <div class="col-md-6">
                    <div class="financial-card bg-precio d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Precio Venta (Total)</h6>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 fw-bold me-2">$</h3>
                                <input type="number" step="0.01" min="0" class="form-control bg-transparent text-white border-0 shadow-none fw-bold p-0" style="font-size: 1.4rem;" id="precio_final" name="precio_final" placeholder="0.00" value="{{ old('precio_final', $precioBaseTotal) }}" required>
                            </div>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>

            <hr class="mb-4">

            <!-- Inputs de Prima y Fecha -->
            <div class="row g-3 mb-2">
                <div class="col-md-6 mb-3">
                    <label for="primer_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-money-bill-wave text-success"></i> Prima Total (Incluye anticipo de ${{ number_format($reserva->monto_reserva, 2) }}) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-success text-white border-success">$</span>
                        <input type="number" step="0.01" min="0" class="form-control border-success font-weight-bold text-success" id="primer_abono" name="primer_abono" value="{{ old('primer_abono', $reserva->monto_reserva) }}" required>
                    </div>
                    <small class="text-muted">Monto total pagado como prima/enganche al momento de formalizar.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_ultimo_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-calendar-alt text-primary"></i> Fecha de Pago de Prima <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar text-muted"></i></span>
                        <input type="date" class="form-control border-start-0 ps-0" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ old('fecha_ultimo_abono', date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <!-- Datos de pago de la Prima con Selector Rápido de 1 Clic -->
            <div class="row g-3 bg-white p-3 mb-3 rounded border shadow-sm">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold text-secondary d-block">
                        <i class="fas fa-wallet text-primary me-1"></i> Método de Pago (Prima) <span class="text-danger">*</span>
                    </label>
                    <div class="btn-group w-100 d-flex flex-wrap shadow-sm" role="group" id="group_metodo_pago">
                        <input type="radio" class="btn-check" name="metodo_pago" id="metodo_efectivo" value="Efectivo" autocomplete="off" {{ old('metodo_pago', 'Efectivo') == 'Efectivo' ? 'checked' : '' }} onchange="toggleMetodoFields()">
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
                    <input type="text" class="form-control" id="referencia" name="referencia" value="Formalización de Reserva #{{ $reserva->id_reserva }}" placeholder="Formalización de Reserva #{{ $reserva->id_reserva }}">
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="plazo_meses" class="form-label font-weight-bold text-secondary">Plazo de Financiamiento <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" min="1" class="form-control" id="plazo_meses" name="plazo_meses" value="{{ old('plazo_meses', 60) }}" placeholder="Ej: 60" required>
                        <span class="input-group-text">Meses</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cuota_mensual" class="form-label font-weight-bold text-secondary">Cuota Mensual Sugerida <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text text-danger font-weight-bold">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold" style="font-size: 1.1rem; color: #e74a3b;" id="cuota_mensual" name="cuota_mensual" placeholder="0.00" value="{{ old('cuota_mensual') }}" required>
                    </div>
                    <small class="text-muted">Se recalcula automáticamente.</small>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-center">
                    <button type="button" id="btnCalcular" class="btn btn-info w-100 py-2 font-weight-bold shadow-sm"><i class="fas fa-calculator me-1"></i> Recalcular Cuota</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <a href="{{ route('reservas.index') }}" class="btn btn-secondary btn-lg me-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="button" id="btn-preparar-formalizacion" class="btn btn-success btn-lg shadow-sm px-5 py-2">
            <i class="fas fa-check-double me-1"></i> Revisar y Confirmar Formalización
        </button>
    </div>
</form>

{{-- ================================================= --}}
{{-- MODAL DE RESUMEN Y CONFIRMACIÓN DE FORMALIZACIÓN --}}
{{-- ================================================= --}}
<div class="modal fade" id="modalConfirmarFormalizacion" tabindex="-1" aria-labelledby="modalConfirmarFormalizacionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalConfirmarFormalizacionLabel">
                    <i class="fas fa-check-circle me-2"></i> Resumen de Formalización y Generación de Venta
                </h5>
                <button type="button" class="close text-white" id="btn-x-modal-formalizar" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Cliente</span>
                            <span class="fs-5 fw-bold text-dark d-block">{{ $reserva->cliente->nombres_apellidos }}</span>
                            <span class="small text-muted d-block">Cédula: {{ $reserva->cliente->identificacion }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Proyecto</span>
                            <span class="badge bg-primary text-white fs-6 mb-1 px-3 py-1">
                                <i class="fas fa-building me-1"></i> {{ $reserva->lotificacion->nombre }}
                            </span>
                            <div class="small text-muted mt-1">Reserva Original: <strong>#{{ $reserva->id_reserva }}</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Lotes que pasan a Venta -->
                <div class="p-3 bg-light rounded border mb-3">
                    <span class="text-success d-block small text-uppercase fw-bold mb-2">
                        <i class="fas fa-check-circle me-1"></i> Lote(s) a Formalizar en la Venta
                    </span>
                    <div id="modal-formalizar-lotes-container">
                        <!-- Badges -->
                    </div>
                    <div class="mt-2 text-dark small">
                        Extensión Total: <strong class="text-info fw-bold" id="modal-formalizar-extension">-</strong>
                    </div>
                </div>

                <!-- Lotes que se liberarán -->
                <div class="p-3 bg-light rounded border mb-3" id="modal-container-liberados" style="display: none;">
                    <span class="text-danger d-block small text-uppercase fw-bold mb-2">
                        <i class="fas fa-undo me-1"></i> Lote(s) que se Liberarán (Quedarán Disponibles)
                    </span>
                    <div id="modal-liberados-lotes-container">
                        <!-- Badges -->
                    </div>
                </div>

                <!-- Desglose Financiero -->
                <div class="card border-success mb-2">
                    <div class="card-body p-3 bg-light">
                        <div class="row text-center align-items-center">
                            <div class="col-3 border-end">
                                <span class="text-muted small d-block">Precio Venta</span>
                                <span class="fs-5 fw-bold text-dark" id="modal-formalizar-precio">$0.00</span>
                            </div>
                            <div class="col-3 border-end">
                                <span class="text-muted small d-block">Prima Inicial</span>
                                <span class="fs-5 fw-bold text-success" id="modal-formalizar-prima">$0.00</span>
                            </div>
                            <div class="col-3 border-end">
                                <span class="text-muted small d-block">Plazo</span>
                                <span class="fs-6 fw-bold text-dark" id="modal-formalizar-plazo">0 Meses</span>
                            </div>
                            <div class="col-3">
                                <span class="text-muted small d-block">Cuota Mensual</span>
                                <span class="fs-5 fw-bold text-danger" id="modal-formalizar-cuota">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 border-top">
                <button type="button" class="btn btn-secondary text-white px-4 fw-bold" id="btn-cancelar-modal-formalizar" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="fas fa-edit me-1"></i> Modificar Datos
                </button>
                <button type="button" id="btn-confirmar-guardar-formalizar" class="btn btn-success text-white px-4 fw-bold shadow-sm">
                    <i class="fas fa-check-double me-1"></i> Confirmar y Generar Contrato
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleMetodoFields() {
    var metodo = $('#metodo_pago').val();
    if (metodo === 'Efectivo') {
        $('#div_cuenta').hide();
        $('#label_referencia').text('Referencia / Comentarios');
    } else {
        $('#div_cuenta').show();
        $('#label_referencia').html('N° Referencia / Transferencia <span class="text-danger">*</span>');
    }
}

$(document).ready(function() {
    toggleMetodoFields();

    var factorVara = 1.418415;

    function recalcularTotalesLotes() {
        var totalMetros = 0;
        var totalPrecio = 0;
        var seleccionadosCount = 0;

        $('.check-lote').each(function() {
            var id = $(this).val();
            var isChecked = $(this).is(':checked');
            var area = parseFloat($(this).data('area')) || 0;
            var precio = parseFloat($(this).data('precio')) || 0;

            if (isChecked) {
                totalMetros += area;
                totalPrecio += precio;
                seleccionadosCount++;
                $('#fila_lote_' + id).removeClass('row-unselected');
                $('#badge_lote_' + id).removeClass('bg-secondary bg-warning text-dark').addClass('bg-success text-white')
                    .html('<i class="fas fa-check me-1"></i> Incluir en Venta');
            } else {
                $('#fila_lote_' + id).addClass('row-unselected');
                $('#badge_lote_' + id).removeClass('bg-success text-white').addClass('bg-secondary text-white')
                    .html('<i class="fas fa-undo me-1"></i> Se liberará a Disponible');
            }
        });

        // Convertir m² a vrs²
        var totalVaras = totalMetros * factorVara;
        if (Math.abs(Math.round(totalVaras) - totalVaras) < 0.02) {
            totalVaras = Math.round(totalVaras);
        }

        $('#display_extension').html(totalVaras.toFixed(2) + ' <small class="fs-6">vrs²</small> <span class="fs-6 font-weight-normal text-white-50 ms-2">(' + totalMetros.toFixed(2) + ' m²)</span>');
        $('#precio_final').val(totalPrecio.toFixed(2));

        // Animación sutil de feedback
        $('.financial-card').css('transform', 'scale(1.02)');
        setTimeout(function() {
            $('.financial-card').css('transform', 'scale(1)');
        }, 200);

        calculateQuota();
    }

    function calculateQuota() {
        let precio = parseFloat($('#precio_final').val()) || 0;
        let plazo = parseInt($('#plazo_meses').val()) || 0;

        if (precio > 0 && plazo > 0) {
            let cuota = precio / plazo;
            $('#cuota_mensual').val((cuota > 0 ? cuota : 0).toFixed(2));
        } else {
            $('#cuota_mensual').val('');
        }
    }

    // Eventos de checkboxes individuales
    $('.check-lote').on('change', function() {
        recalcularTotalesLotes();
    });

    // Checkbox seleccionar todos
    $('#check-todos-lotes').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.check-lote').prop('checked', isChecked);
        recalcularTotalesLotes();
    });

    $('#btnCalcular').click(function() {
        let precio = parseFloat($('#precio_final').val()) || 0;
        let plazo = parseInt($('#plazo_meses').val()) || 0;

        if (plazo < 1 || precio <= 0) {
            alert('Asegúrese de que el Precio sea mayor a 0 y el Plazo sea al menos 1 mes.');
        } else {
            calculateQuota();
        }
    });

    $('#precio_final, #primer_abono, #plazo_meses').on('input', function() {
        calculateQuota();
    });

    // Evitar scroll en inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
    });

    // Modal de confirmación
    var modalFormalizarEl = document.getElementById('modalConfirmarFormalizacion');
    var modalFormalizar = new bootstrap.Modal(modalFormalizarEl);
    var formFormalizar = document.getElementById('form-formalizar');

    $('#btn-preparar-formalizacion').on('click', function(e) {
        e.preventDefault();

        if (!formFormalizar.checkValidity()) {
            formFormalizar.reportValidity();
            return;
        }

        var seleccionados = $('.check-lote:checked');
        if (seleccionados.length === 0) {
            alert('Debe seleccionar al menos un lote para formalizar la venta.');
            return;
        }

        var containerFormalizados = $('#modal-formalizar-lotes-container');
        var containerLiberados = $('#modal-liberados-lotes-container');
        containerFormalizados.empty();
        containerLiberados.empty();

        var liberadosCount = 0;

        $('.check-lote').each(function() {
            var loteNum = $(this).data('lote-num');
            var bloqueName = $(this).data('bloque');
            if ($(this).is(':checked')) {
                containerFormalizados.append('<span class="badge bg-dark text-white me-1 mb-1 fs-6 px-3 py-2">Bloque ' + bloqueName + ' - Lote ' + loteNum + '</span>');
            } else {
                liberadosCount++;
                containerLiberados.append('<span class="badge bg-danger text-white me-1 mb-1 fs-6 px-3 py-2"><i class="fas fa-unlock me-1"></i> Bloque ' + bloqueName + ' - Lote ' + loteNum + '</span>');
            }
        });

        if (liberadosCount > 0) {
            $('#modal-container-liberados').show();
        } else {
            $('#modal-container-liberados').hide();
        }

        $('#modal-formalizar-extension').text($('#display_extension').text());

        var precioVal = parseFloat($('#precio_final').val()) || 0;
        var primaVal = parseFloat($('#primer_abono').val()) || 0;
        var plazoVal = parseInt($('#plazo_meses').val()) || 0;
        var cuotaVal = parseFloat($('#cuota_mensual').val()) || 0;

        $('#modal-formalizar-precio').text('$' + precioVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#modal-formalizar-prima').text('$' + primaVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#modal-formalizar-plazo').text(plazoVal + ' Meses');
        $('#modal-formalizar-cuota').text('$' + cuotaVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        modalFormalizar.show();
    });

    // Cerrar modal al hacer clic en "Modificar Datos" o en "X"
    $('#btn-cancelar-modal-formalizar, #btn-x-modal-formalizar').on('click', function(e) {
        e.preventDefault();
        try { modalFormalizar.hide(); } catch(err) {}
        $('#modalConfirmarFormalizacion').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    $('#btn-confirmar-guardar-formalizar').on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generando Contrato...');
        formFormalizar.submit();
    });

    // Inicializar cálculos
    recalcularTotalesLotes();
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
