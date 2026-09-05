@extends('template') {{-- 1. Hereda la plantilla principal --}}

@section('titulo', 'Registro de Clientes') {{-- 2. Define el contenido de la sección 'titulo' --}}

@section('contenido') {{-- 3. Abre la sección principal 'contenido' --}}

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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-plus text-primary"></i> Registro de Cliente y Promesa de Venta</h1>
</div>

<form id="form-registro-venta" action="{{ route('registro.store') }}" method="POST">
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
                    <input type="text" class="form-control text-uppercase" id="nombre_completo" name="nombres_apellidos" value="{{ old('nombres_apellidos') }}" style="text-transform: uppercase;" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cedula" class="form-label font-weight-bold text-secondary">Cédula <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="cedula" name="identificacion" value="{{ old('identificacion') }}" placeholder="000-000000-0000A" maxlength="16" style="text-transform: uppercase;" required>
                    <small class="text-muted"><i class="fas fa-id-card text-primary"></i> Formato: XXX-XXXXXX-XXXXX</small>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="pv_num" class="form-label font-weight-bold text-secondary">N° PV (Promesa)</label>
                    <input type="text" class="form-control text-uppercase" id="pv_num" name="pv_num" value="{{ old('pv_num', 'PP') }}" placeholder="PP" style="text-transform: uppercase;">
                    <small class="text-muted"><i class="fas fa-info-circle text-info"></i> Por defecto PP</small>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="expediente_num" class="form-label font-weight-bold text-secondary">N° Expediente</label>
                    <input type="text" class="form-control bg-light font-monospace fw-bold text-primary text-uppercase" id="expediente_num" name="expediente_num" value="{{ old('expediente_num', $siguienteExpediente ?? '') }}" style="text-transform: uppercase;" readonly>
                    <small class="text-muted"><i class="fas fa-magic text-primary"></i> Automático</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="telefono" class="form-label font-weight-bold text-secondary">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="+505 0000-0000">
                </div>
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
                    <input type="text" class="form-control text-uppercase" id="profesion_oficio" name="profesion_oficio" value="{{ old('profesion_oficio') }}" style="text-transform: uppercase;">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="domicilio" class="form-label font-weight-bold text-secondary">Domicilio / Municipio</label>
                    <input type="text" class="form-control text-uppercase" id="domicilio" name="domicilio" value="{{ old('domicilio') }}" placeholder="Ej: San Miguel, San Rafael del Sur" style="text-transform: uppercase;">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="direccion" class="form-label font-weight-bold text-secondary">Dirección Exacta</label>
                    <textarea class="form-control text-uppercase" id="direccion" name="direccion" rows="1" style="text-transform: uppercase;">{{ old('direccion') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: ASIGNACIÓN DE LOTES (INVENTARIO) --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-layer-group"></i> Asignación de Terreno / Lotes</h6>
        </div>
        <div class="card-body bg-light">

            {{-- TIPO DE CONTRATO --}}
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="form-label font-weight-bold text-secondary d-block">
                        <i class="fas fa-file-contract text-info me-1"></i> Tipo de Contrato <span class="text-danger">*</span>
                    </label>
                    <div class="btn-group w-100" role="group" id="group_tipo_contrato">
                        <input type="radio" class="btn-check" name="tipo_contrato" id="tipo_unificado" value="unificado" autocomplete="off" checked>
                        <label class="btn btn-outline-primary py-2 fw-bold" for="tipo_unificado">
                            <i class="fas fa-layer-group me-1"></i> Contrato Unificado
                            <small class="d-block fw-normal opacity-75">Un plan de pago para todos los lotes</small>
                        </label>
                        <input type="radio" class="btn-check" name="tipo_contrato" id="tipo_individual" value="individual" autocomplete="off">
                        <label class="btn btn-outline-warning py-2 fw-bold" for="tipo_individual">
                            <i class="fas fa-user-friends me-1"></i> Contratos por Lote
                            <small class="d-block fw-normal opacity-75">Cada lote con su propio plan y beneficiario</small>
                        </label>
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <i class="fas fa-info-circle text-warning"></i>
                        <strong>Contratos por Lote</strong>: Úselo cuando cada lote tiene un futuro propietario diferente (hermanos, socios, familiares). Cada lote genera su propio plan de cuotas independiente.
                    </small>
                </div>
            </div>

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
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Para registrar en otro proyecto, cámbielo en la barra superior.</small>
                </div>

                <!-- Bloque -->
                <div class="col-md-4 mb-3">
                    <label for="bloque_select" class="form-label font-weight-bold text-secondary">Bloque / Manzana <span class="text-danger">*</span></label>
                    <select class="custom-select form-control" id="bloque_select" name="id_bloque" required>
                        <option value="">-- Seleccionar Bloque --</option>
                        @foreach ($bloques as $bloque)
                            <option value="{{ $bloque->id_bloque }}" @selected(old('id_bloque') == $bloque->id_bloque)>
                                {{ $bloque->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Lotes (Múltiple) -->
                <div class="col-md-4 mb-3">
                    <label for="lote_select" class="form-label font-weight-bold text-secondary">Lote(s) a Asignar <span class="text-danger">*</span></label>
                    <select class="custom-select form-control" id="lote_select" name="lotes_ids[]" multiple="multiple" required disabled>
                    </select>
                    <small class="text-muted" id="hint_lote_select">Puede seleccionar múltiples lotes si es una venta unificada.</small>
                </div>
            </div>

            {{-- BENEFICIARIO FINAL: solo visible cuando se selecciona "Contratos por Lote" --}}
            <div class="row g-3 mt-2 p-3 border rounded bg-white shadow-sm" id="seccion_beneficiario" style="display:none;">
                <div class="col-12 mb-1">
                    <h6 class="text-warning fw-bold mb-0">
                        <i class="fas fa-user-tie me-1"></i> Beneficiario Final / Futuro Titular
                        <span class="badge bg-warning text-dark ms-1 fw-normal small">Opcional</span>
                    </h6>
                    <small class="text-muted">Si el lote será transferido a otra persona al finalizar los pagos (hermano, socio, familiar), indíquelo aquí. El contrato permanece a nombre del representante hasta que se escriture.</small>
                </div>
                <div class="col-md-5 mb-2">
                    <label for="beneficiario_final" class="form-label font-weight-bold text-secondary">Nombre del Beneficiario Final</label>
                    <input type="text" class="form-control" id="beneficiario_final" name="beneficiario_final" placeholder="Ej: Carlos García (Hermano - USA)" value="{{ old('beneficiario_final') }}">
                </div>
                <div class="col-md-7 mb-2">
                    <label for="nota_beneficiario" class="form-label font-weight-bold text-secondary">Nota / Aclaración Legal</label>
                    <input type="text" class="form-control" id="nota_beneficiario" name="nota_beneficiario" placeholder="Ej: Hermano en el exterior. Escritura final a su nombre al finalizar el contrato." value="{{ old('nota_beneficiario') }}">
                </div>
            </div>

        </div>
    </div>


    {{-- SECCIÓN 3: PLAN FINANCIERO DE LA VENTA --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-hand-holding-usd"></i> Condiciones Financieras del Contrato</h6>
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
                            <input type="hidden" id="extension_lote" name="extension">
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
                                <input type="number" step="0.01" min="0" class="form-control bg-transparent text-white border-0 shadow-none fw-bold p-0" style="font-size: 1.4rem;" id="monto_lote" name="precio_final" placeholder="0.00" value="{{ old('precio_final') }}" required>
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
                    <label for="primer_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-money-bill-wave text-success"></i> Prima / Enganche <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-success text-white border-success">$</span>
                        <input type="number" step="0.01" min="0" class="form-control border-success" id="primer_abono" name="primer_abono" placeholder="0.00" value="{{ old('primer_abono') }}" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_ultimo_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-calendar-alt text-primary"></i> Fecha del 1° Pago <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar text-muted"></i></span>
                        <input type="date" class="form-control border-start-0 ps-0" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ old('fecha_ultimo_abono', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <!-- Datos de pago de la Prima con Selector Rápido de 1 Clic -->
            <div class="row g-3 bg-white p-3 mb-3 rounded border shadow-sm">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold text-secondary d-block">
                        <i class="fas fa-wallet text-primary me-1"></i> Método de Pago (Prima) <span class="text-danger">*</span>
                    </label>
                    <div class="btn-group w-100 d-flex flex-wrap shadow-sm" role="group" id="group_metodo_pago_prima">
                        <input type="radio" class="btn-check" name="metodo_pago_prima" id="metodo_prima_efectivo" value="Efectivo" autocomplete="off" {{ old('metodo_pago_prima', 'Efectivo') == 'Efectivo' ? 'checked' : '' }} onchange="togglePrimaFields()">
                        <label class="btn btn-outline-success py-2 fw-bold flex-fill" for="metodo_prima_efectivo">
                            <i class="fas fa-money-bill-wave me-1"></i> Efectivo
                        </label>

                        <input type="radio" class="btn-check" name="metodo_pago_prima" id="metodo_prima_transferencia" value="Transferencia Bancaria" autocomplete="off" {{ old('metodo_pago_prima') == 'Transferencia Bancaria' ? 'checked' : '' }} onchange="togglePrimaFields()">
                        <label class="btn btn-outline-primary py-2 fw-bold flex-fill" for="metodo_prima_transferencia">
                            <i class="fas fa-exchange-alt me-1"></i> Transferencia
                        </label>

                        <input type="radio" class="btn-check" name="metodo_pago_prima" id="metodo_prima_deposito" value="Depósito Bancario" autocomplete="off" {{ old('metodo_pago_prima') == 'Depósito Bancario' ? 'checked' : '' }} onchange="togglePrimaFields()">
                        <label class="btn btn-outline-info py-2 fw-bold flex-fill" for="metodo_prima_deposito">
                            <i class="fas fa-university me-1"></i> Depósito
                        </label>
                    </div>
                </div>
                <div class="col-md-3 mb-3" id="div_cuenta_prima" style="display: none;">
                    <label for="cuenta_destino_prima" class="form-label font-weight-bold text-secondary">Cuenta Destino</label>
                    <input type="text" class="form-control" id="cuenta_destino_prima" name="cuenta_destino_prima" placeholder="Ej: BANPRO - Empresa">
                </div>
                <div class="col-md-3 mb-3" id="div_referencia_prima">
                    <label for="referencia_prima" class="form-label font-weight-bold text-secondary" id="label_referencia_prima">Comentarios / Referencia</label>
                    <input type="text" class="form-control" id="referencia_prima" name="referencia_prima" placeholder="Registro Inicial de Venta">
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="plazo_cuotas" class="form-label font-weight-bold text-secondary">Plazo de Financiamiento <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="plazo_cuotas" name="plazo_meses" value="{{ old('plazo_meses') }}" required placeholder="Ej: 60">
                        <span class="input-group-text">Meses</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cuotas" class="form-label font-weight-bold text-secondary">Cuota Mensual Sugerida <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text text-danger font-weight-bold">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold" style="font-size: 1.1rem; color: #e74a3b;" id="cuotas" name="cuota_mensual" placeholder="0.00" value="{{ old('cuota_mensual') }}" required>
                    </div>
                    <small class="text-muted">Monto recalculado automáticamente.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <a href="{{ route('registro.index') }}" class="btn btn-secondary btn-lg me-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="button" id="btn-preparar-registro" class="btn btn-success btn-lg shadow-sm px-5 py-2">
            <i class="fas fa-check-circle me-1"></i> Revisar y Registrar Venta
        </button>
    </div>
</form>

{{-- ================================================= --}}
{{-- MODAL DE RESUMEN Y CONFIRMACIÓN DE VENTA --}}
{{-- ================================================= --}}
<div class="modal fade" id="modalConfirmarVenta" tabindex="-1" aria-labelledby="modalConfirmarVentaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalConfirmarVentaLabel">
                    <i class="fas fa-file-contract me-2"></i> Resumen de Promesa de Venta
                </h5>
                <button type="button" class="close text-white" id="btn-x-modal-venta" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center border-info">
                    <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                    <div>
                        <strong class="text-dark">Confirmación Consciente de Venta:</strong>
                        <div class="small text-secondary">Verifique que todos los datos del cliente, asignación de lotes y condiciones de financiamiento sean correctos antes de guardar.</div>
                    </div>
                </div>

                {{-- PROYECTO & CLIENTE --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Cliente / Titular</span>
                            <span class="fs-5 fw-bold text-dark d-block" id="modal-resumen-cliente">-</span>
                            <div class="small text-dark mt-1">
                                <span>Cédula: <strong class="text-dark font-weight-bold" id="modal-resumen-cedula">-</strong></span> &middot;
                                <span>Tel: <strong class="text-dark font-weight-bold" id="modal-resumen-telefono">-</strong></span>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-secondary text-white me-1 px-2 py-1" id="modal-resumen-pv">PV: -</span>
                                <span class="badge bg-dark text-white px-2 py-1" id="modal-resumen-exp">Exp: -</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <span class="text-secondary d-block small text-uppercase fw-bold">Proyecto y Ubicación</span>
                            <span class="badge bg-primary text-white fs-6 mb-2 mt-1 px-3 py-1">
                                <i class="fas fa-map-marker-alt me-1"></i> {{ $lotificacionActiva->nombre ?? 'Proyecto Activo' }}
                            </span>
                            <div class="text-dark">
                                <span>Bloque: <strong class="text-primary fw-bold" id="modal-resumen-bloque">-</strong></span>
                            </div>
                            <div class="mt-1" id="modal-resumen-lotes-container">
                                <!-- Badges de lotes -->
                            </div>
                            <div class="small text-dark mt-2">
                                Extensión Total: <strong class="text-dark font-weight-bold" id="modal-resumen-extension">0.00 vrs²</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PLAN FINANCIERO --}}
                <div class="card border-primary mb-2">
                    <div class="card-header bg-light py-2 border-bottom">
                        <strong class="text-primary small text-uppercase"><i class="fas fa-coins me-1"></i> Plan Financiero y Cuotas</strong>
                    </div>
                    <div class="card-body p-3 bg-white">
                        <div class="row text-center align-items-center">
                            <div class="col-6 col-md-3 border-end">
                                <span class="text-muted small d-block">Precio Total</span>
                                <span class="fs-5 fw-bold text-dark" id="modal-resumen-precio">$0.00</span>
                            </div>
                            <div class="col-6 col-md-3 border-end">
                                <span class="text-muted small d-block">Prima Inicial</span>
                                <span class="fs-5 fw-bold text-success" id="modal-resumen-prima">$0.00</span>
                            </div>
                            <div class="col-6 col-md-3 border-end">
                                <span class="text-muted small d-block">Plazo Total</span>
                                <span class="fs-5 fw-bold text-primary" id="modal-resumen-plazo">0 Meses</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted small d-block">Cuota Mensual</span>
                                <span class="fs-5 fw-bold text-danger" id="modal-resumen-cuota">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 border-top">
                <button type="button" class="btn btn-secondary text-white px-4 fw-bold" id="btn-cancelar-modal-venta" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="fas fa-edit me-1"></i> Modificar Datos
                </button>
                <button type="button" id="btn-confirmar-guardar-venta" class="btn btn-success text-white px-4 fw-bold shadow-sm">
                    <i class="fas fa-check-circle me-1"></i> Confirmar y Registrar Venta
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
$(document).ready(function() {
    // Inicializar Select2
    $('#proyecto_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Escoger Proyecto --'
    });
    
    $('#bloque_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione un Proyecto primero'
    });
    
    $('#lote_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione uno o más Lotes',
        allowClear: true
    });

    // Evitar que el scroll del mouse cambie el valor de los inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
    });

    function calcularCuota() {
        var monto = parseFloat($('#monto_lote').val()) || 0;
        var plazo = parseInt($('#plazo_cuotas').val()) || 0;
        var prima = parseFloat($('#primer_abono').val()) || 0;

        // Como no hay "prima" tradicional, la cuota es fija: Precio Total / Plazo Total.
        // El abono inicial simplemente paga la primera(s) cuota(s), pero no cambia el valor de la cuota mensual.
        if (monto > 0 && plazo > 0) {
            var cuota = monto / plazo;
            $('#cuotas').val((cuota > 0 ? cuota : 0).toFixed(2));
            
            // Sugerencia visual: si el usuario aún no ha escrito un abono inicial, 
            // sugerimos que sea igual a la cuota mensual.
            if ($('#primer_abono').val() == '' || parseFloat($('#primer_abono').val()) == 0) {
                // Se puede habilitar si se desea autocompletar el primer pago
                // $('#primer_abono').val(cuota.toFixed(2));
            }
        } else {
            $('#cuotas').val('');
        }
    }

    $('#bloque_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione Bloque'
    });

    $('#bloque_select').change(function() {
        var bloqueId = $(this).val();
        var loteSelect = $('#lote_select');

        loteSelect.html('<option value=""></option>').prop('disabled', true);
        loteSelect.select2('destroy').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Cargando lotes...' });
        $('#extension_lote').val('');
        $('#extension_lote_value').val('');
        $('#monto_lote').val('');
        calcularCuota();

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

        // Ajuste de precisión visual: si el cálculo está absurdamente cerca de un número exacto 
        // (por la pérdida de decimales al guardar en metros), lo redondeamos para que se vea limpio.
        if (Math.abs(Math.round(totalExtensionVaras) - totalExtensionVaras) < 0.02) {
            totalExtensionVaras = Math.round(totalExtensionVaras);
        }

        // Guardar el valor en m² para la base de datos (o usar vrs² si la BD guarda vrs²)
        // NOTA: Asumimos que quieres guardar m² en la BD, si no, cambialo.
        $('#extension_lote').val(totalExtensionMetros.toFixed(2));
        $('#extension_lote_value').val(totalExtensionMetros.toFixed(2));
        $('#display_extension').html(totalExtensionVaras.toFixed(2) + ' <small class="fs-6">vrs²</small> <span class="fs-6 font-weight-normal text-white-50 ms-2">(' + totalExtensionMetros.toFixed(2) + ' m²)</span>');
        $('#monto_lote').val(totalMonto.toFixed(2));
        
        // Micro-animación para dar feedback visual
        $('.financial-card').css('transform', 'scale(1.02)');
        setTimeout(function() {
            $('.financial-card').css('transform', 'scale(1)');
        }, 200);

        calcularCuota();
    });

    // El monto, el plazo y la prima siguen siendo editables: recalculan la
    // cuota sugerida, pero el usuario puede ajustarla manualmente después.
    $('#monto_lote, #plazo_cuotas, #primer_abono').on('input', calcularCuota);
    
    // Disparar el evento change si hay un bloque preseleccionado al cargar la página
    if ($('#bloque_select').val()) {
        $('#bloque_select').trigger('change');
    }

    // Modal de Confirmación Consciente de Venta
    var modalConfirmarVentaEl = document.getElementById('modalConfirmarVenta');
    var modalConfirmarVenta = new bootstrap.Modal(modalConfirmarVentaEl);
    var formRegistro = document.getElementById('form-registro-venta');

    $('#btn-preparar-registro').on('click', function(e) {
        e.preventDefault();

        if (!formRegistro.checkValidity()) {
            formRegistro.reportValidity();
            return;
        }

        var lotesSeleccionados = $('#lote_select').val();
        if (!lotesSeleccionados || lotesSeleccionados.length === 0) {
            alert('Debe seleccionar al menos un lote para poder realizar la venta.');
            $('#lote_select').select2('open');
            return;
        }

        // Poblar datos en el modal
        $('#modal-resumen-cliente').text($('#nombre_completo').val() || '-');
        $('#modal-resumen-cedula').text($('#cedula').val() || '-');
        $('#modal-resumen-telefono').text($('#telefono').val() || 'No indicado');
        $('#modal-resumen-pv').text('PV: ' + ($('#pv_num').val() || '-'));
        $('#modal-resumen-exp').text('Exp: ' + ($('#expediente_num').val() || '-'));

        var bloqueTexto = $('#bloque_select option:selected').text();
        $('#modal-resumen-bloque').text(bloqueTexto.trim());

        // Lotes badges
        var containerLotes = $('#modal-resumen-lotes-container');
        containerLotes.empty();
        $('#lote_select option:selected').each(function() {
            containerLotes.append('<span class="badge bg-dark text-white me-1 mb-1">Lote ' + $(this).text() + '</span>');
        });

        var extMetros = parseFloat($('#extension_lote').val()) || 0;
        var factorVara = 1.418415;
        var extVaras = extMetros * factorVara;
        if (Math.abs(Math.round(extVaras) - extVaras) < 0.02) {
            extVaras = Math.round(extVaras);
        }
        $('#modal-resumen-extension').html(parseFloat(extVaras).toFixed(2) + ' vrs² <span class="text-muted ms-1">(' + extMetros.toFixed(2) + ' m²)</span>');

        var precioVal = parseFloat($('#monto_lote').val()) || 0;
        var primaVal = parseFloat($('#primer_abono').val()) || 0;
        var plazoVal = parseInt($('#plazo_cuotas').val()) || 0;
        var cuotaVal = parseFloat($('#cuotas').val()) || 0;

        $('#modal-resumen-precio').text('$' + precioVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#modal-resumen-prima').text('$' + primaVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#modal-resumen-plazo').text(plazoVal + ' Meses');
        $('#modal-resumen-cuota').text('$' + cuotaVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        modalConfirmarVenta.show();
    });

    // Cerrar modal al hacer clic en "Modificar Datos" o en "X"
    $('#btn-cancelar-modal-venta, #btn-x-modal-venta').on('click', function(e) {
        e.preventDefault();
        try {
            modalConfirmarVenta.hide();
        } catch(err) {}
        $('#modalConfirmarVenta').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    $('#btn-confirmar-guardar-venta').on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Registrando Venta...');
        document.getElementById('form-registro-venta').submit();
    });
});
</script>
            <script src="{{ asset('js/jqueryEM.js') }}"></script>

        <!-- Custom scripts for all pages-->
    
        <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    
    <script>
    function togglePrimaFields() {
        var checkedRadio = document.querySelector('input[name="metodo_pago_prima"]:checked');
        var metodo = checkedRadio ? checkedRadio.value : (document.getElementById('metodo_pago_prima') ? document.getElementById('metodo_pago_prima').value : 'Efectivo');
        var divCuenta = document.getElementById('div_cuenta_prima');
        var inputCuenta = document.getElementById('cuenta_destino_prima');
        var labelRef = document.getElementById('label_referencia_prima');
        var inputRef = document.getElementById('referencia_prima');
        var divRef = document.getElementById('div_referencia_prima');

        if (metodo === 'Transferencia Bancaria' || metodo === 'Depósito Bancario') {
            if (divCuenta) divCuenta.style.display = 'block';
            if (inputCuenta) inputCuenta.required = true;
            if (divRef) divRef.className = 'col-md-3 mb-3';
            if (labelRef) labelRef.innerText = 'N° de Referencia / Comprobante';
            if (inputRef) {
                inputRef.placeholder = 'N° de transacción';
                inputRef.required = true;
            }
        } else {
            if (divCuenta) divCuenta.style.display = 'none';
            if (inputCuenta) {
                inputCuenta.required = false;
                inputCuenta.value = '';
            }
            if (divRef) divRef.className = 'col-md-6 mb-3';
            if (labelRef) labelRef.innerText = 'Comentarios / Referencia';
            if (inputRef) {
                inputRef.placeholder = 'Registro Inicial de Venta';
                inputRef.required = false;
            }
        }
    }
    
    // Mostrar u ocultar la sección de Beneficiario Final según el tipo de contrato
    function toggleBeneficiario() {
        var esIndividual = document.getElementById('tipo_individual') && document.getElementById('tipo_individual').checked;
        var seccion = document.getElementById('seccion_beneficiario');
        if (seccion) {
            seccion.style.display = esIndividual ? 'flex' : 'none';
        }
    }

    // Formateador automático de Cédula (XXX-XXXXXX-XXXXX)
    function formatearCedula(input) {
        let cursor = input.selectionStart;
        let originalLen = input.value.length;
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

    // Ejecutar al cargar la página por si hay valores "old"
    document.addEventListener("DOMContentLoaded", function() {
        togglePrimaFields();
        toggleBeneficiario();

        // Escuchar cambios en los radio de tipo de contrato
        document.querySelectorAll('input[name="tipo_contrato"]').forEach(function(radio) {
            radio.addEventListener('change', toggleBeneficiario);
        });

        // Máscara y mayúsculas en cédula
        const cedulaInput = document.getElementById('cedula');
        if (cedulaInput) {
            cedulaInput.addEventListener('input', function() {
                formatearCedula(this);
            });
            cedulaInput.addEventListener('blur', function() {
                formatearCedula(this);
            });
        }

        // Forzar mayúsculas en todos los campos con clase text-uppercase
        document.querySelectorAll('.text-uppercase').forEach(function(el) {
            el.addEventListener('input', function() {
                if (this.id !== 'cedula') {
                    this.value = this.value.toUpperCase();
                }
            });
        });
    });
    </script>
@endsection