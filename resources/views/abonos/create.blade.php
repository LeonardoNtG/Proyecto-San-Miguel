@extends('template')

@section('titulo', 'Registrar Abono: ' . $cliente->nombres_apellidos)

@section('contenido')

<style>
    .cursor-pointer { cursor: pointer; }
    
    /* Contenedor Wizard Minimalista */
    .wizard-card {
        max-width: 850px;
        margin: 0 auto;
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    
    /* Indicador de Pasos Limpio */
    .wizard-steps {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2rem;
        position: relative;
    }
    .wizard-step {
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
    }
    .wizard-step-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #f1f3f9;
        border: 2px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }
    .wizard-step-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #64748b;
        margin-left: 8px;
        margin-right: 18px;
    }
    .wizard-step-line {
        width: 40px;
        height: 2px;
        background: #e2e8f0;
        margin-right: 18px;
    }
    .wizard-step.active .wizard-step-circle {
        background: #4e73df;
        border-color: #4e73df;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.2);
    }
    .wizard-step.active .wizard-step-label {
        color: #4e73df;
        font-weight: 700;
    }
    .wizard-step.completed .wizard-step-circle {
        background: #1cc88a;
        border-color: #1cc88a;
        color: #fff;
    }
    .wizard-step.completed .wizard-step-label {
        color: #1cc88a;
    }
    .wizard-step.completed + .wizard-step-line,
    .wizard-step.active + .wizard-step-line {
        background: #4e73df;
    }

    /* Paneles de pasos */
    .wizard-pane {
        display: none;
        animation: fadeIn 0.25s ease-in-out;
    }
    .wizard-pane.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Tarjetas de Selección de Lotes */
    .lote-item-box {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        background: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .lote-item-box:hover {
        border-color: #4e73df;
        background: #f8fafc;
    }
    .lote-item-box.selected {
        border-color: #4e73df;
        background: #f0f4ff;
        box-shadow: 0 2px 8px rgba(78, 115, 223, 0.12);
    }

    /* Botones de Método de Pago */
    .metodo-btn-box {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }
    .metodo-btn-box:hover {
        border-color: #1cc88a;
        background: #f8fafc;
    }
    .btn-check:checked + .metodo-btn-box {
        border-color: #1cc88a;
        background: #e8fbf4;
        color: #0f6848;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(28, 200, 138, 0.15);
    }
</style>

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

    {{-- CABECERA MINIMALISTA Y ELEGANTE --}}
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h3 class="mb-1 text-gray-800 fw-bold">
                <i class="fas fa-cash-register text-success me-2"></i> Registrar Abono
            </h3>
            <div class="text-muted">
                Cliente: <strong class="text-dark">{{ $cliente->nombres_apellidos }}</strong>
                <span class="badge bg-light text-secondary border ms-2">Exp: {{ $cliente->expediente_num }}</span>
                <span class="badge bg-light text-secondary border ms-1">PV: {{ $cliente->pv_num }}</span>
                <span class="ms-3 text-muted">Saldo Total Pendiente: <strong class="text-danger">${{ number_format($saldoPendiente, 2) }}</strong></span>
            </div>
        </div>
        <div>
            <a href="{{ route('registro.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    {{-- ASISTENTE PRINCIPAL CENTRADO Y LIMPIO --}}
    <div class="card wizard-card shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">

            {{-- INDICADOR DE PASOS WIZARD --}}
            <div class="wizard-steps" id="wizard-steps-header">
                @if($ventas->count() > 1)
                    <div class="wizard-step active" id="step-nav-1" onclick="irAlPaso(1)">
                        <div class="wizard-step-circle">1</div>
                        <span class="wizard-step-label">Lotes</span>
                    </div>
                    <div class="wizard-step-line"></div>
                    <div class="wizard-step" id="step-nav-2" onclick="irAlPaso(2)">
                        <div class="wizard-step-circle">2</div>
                        <span class="wizard-step-label">Pago</span>
                    </div>
                    <div class="wizard-step-line"></div>
                    <div class="wizard-step" id="step-nav-3" onclick="irAlPaso(3)">
                        <div class="wizard-step-circle">3</div>
                        <span class="wizard-step-label">Confirmar</span>
                    </div>
                @else
                    <div class="wizard-step active" id="step-nav-2" onclick="irAlPaso(2)">
                        <div class="wizard-step-circle">1</div>
                        <span class="wizard-step-label">Monto y Pago</span>
                    </div>
                    <div class="wizard-step-line"></div>
                    <div class="wizard-step" id="step-nav-3" onclick="irAlPaso(3)">
                        <div class="wizard-step-circle">2</div>
                        <span class="wizard-step-label">Confirmar</span>
                    </div>
                @endif
            </div>

            {{-- FORMULARIO --}}
            <form id="form-abono" action="{{ route('abono.store', $cliente->id_cliente) }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- ================================================= --}}
                {{-- PASO 1: SELECCIÓN DE LOTES (Si tiene múltiples) --}}
                {{-- ================================================= --}}
                @if($ventas->count() > 1)
                    <div class="wizard-pane active" id="wizard-pane-1">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-gray-800 mb-1">¿A qué lotes desea abonar el cliente?</h4>
                            <p class="text-muted small">Seleccione los contratos que recibirán parte del pago.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 mt-1" id="btn-toggle-todos-lotes" onclick="toggleTodosLotesCheckboxes()">
                                <i class="fas fa-check-double me-1"></i> Marcar Todos ({{ $ventas->count() }})
                            </button>
                        </div>

                        <div class="row g-3 mb-4">
                            @foreach($ventas as $v)
                                @php
                                    $nombreL = $v->lotes->map(fn($l) => 'Bloque '.($l->bloque->nombre ?? '').' - Lote '.$l->numero_lote)->implode(', ');
                                    $saldoVenta = $v->precio_final - $v->abonos->sum('monto_abonado');
                                @endphp
                                <div class="col-md-6 col-12">
                                    <div class="lote-item-box selected" onclick="toggleLoteCard('{{ $v->id_venta }}')">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center text-truncate pe-2">
                                                <input class="form-check-input check-lote-abono me-3 fs-5" type="checkbox" name="ventas_ids[]" id="chk_venta_{{ $v->id_venta }}" value="{{ $v->id_venta }}" data-cuota="{{ $v->cuota_mensual }}" data-saldo="{{ $saldoVenta }}" data-nombre="{{ $nombreL }}" checked onchange="actualizarSugerenciaMonto(); event.stopPropagation();">
                                                <div class="text-truncate">
                                                    <strong class="text-dark d-block fs-6 text-truncate">{{ $nombreL }}</strong>
                                                    <small class="text-muted d-block text-truncate">
                                                        {{ $v->beneficiario_final ? 'Beneficiario: '.$v->beneficiario_final : 'Titular directo' }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <span class="badge bg-primary text-white fw-bold px-2 py-1 fs-6">${{ number_format($v->cuota_mensual, 2) }}</span>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Saldo: ${{ number_format($saldoVenta, 2) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-muted small d-block">Cuota Sugerida Total:</span>
                                <strong class="fs-4 text-primary" id="span-monto-sugerido">${{ number_format($ventas->sum('cuota_mensual'), 2) }}</strong>
                            </div>
                            <button type="button" class="btn btn-primary btn-lg fw-bold px-4" onclick="irAlPaso(2)">
                                Continuar al Pago <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="id_venta" value="{{ $venta->id_venta }}">
                @endif

                {{-- ================================================= --}}
                {{-- PASO 2: MONTO Y FORMA DE PAGO --}}
                {{-- ================================================= --}}
                <div class="wizard-pane {{ $ventas->count() > 1 ? '' : 'active' }}" id="wizard-pane-2">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-gray-800 mb-1">Monto y Forma de Pago</h4>
                        <p class="text-muted small">Ingrese la cantidad recibida y el método de pago.</p>
                    </div>

                    {{-- MONTO DESTACADO --}}
                    <div class="mb-4 text-center">
                        <label for="monto" class="form-label text-muted small fw-bold text-uppercase">Monto a Ingresar ($)</label>
                        <div class="input-group input-group-lg mx-auto" style="max-width: 320px;">
                            <span class="input-group-text bg-success text-white fw-bold">$</span>
                            <input type="number" step="0.01" min="0.01" max="{{ $deudaMaximaExacta ?? $saldoPendiente }}" class="form-control form-control-lg text-center fs-3 fw-bold text-success border-success" id="monto" name="monto_abonado" placeholder="0.00" value="{{ $cuotaSugeridaTotal > 0 ? number_format($cuotaSugeridaTotal, 2, '.', '') : '' }}" required oninput="actualizarSugerenciaMonto()">
                        </div>
                        <div id="alerta-sobrepago" class="alert alert-danger py-1 px-2 mt-2 mx-auto small fw-bold" style="max-width: 380px; display: none;">
                            <i class="fas fa-exclamation-triangle me-1"></i> El monto no puede superar la deuda total pendiente ($<span id="txt-deuda-maxima">{{ number_format($deudaMaximaExacta ?? $saldoPendiente, 2) }}</span>).
                        </div>
                        <div class="mt-2 d-flex justify-content-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="aplicarMontoSugerido()">
                                <i class="fas fa-magic me-1"></i> Cuota: <span id="span-monto-btn-sugerir">${{ number_format($cuotaSugeridaTotal, 2) }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="aplicarMontoTotal()">
                                <i class="fas fa-check-double me-1"></i> Liquidar Total: ${{ number_format($deudaMaximaExacta ?? $saldoPendiente, 2) }}
                            </button>
                        </div>
                    </div>

                    {{-- SELECTOR DE MÉTODO DE PAGO --}}
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase d-block text-center mb-2">Método de Pago</label>
                        <div class="row g-2 justify-content-center">
                            <div class="col-md-4 col-12">
                                <input type="radio" class="btn-check d-none" name="metodo_pago" id="metodo_efectivo" value="Efectivo" autocomplete="off" checked onchange="toggleMetodoPagoFields()">
                                <label class="metodo-btn-box d-block mb-0" for="metodo_efectivo">
                                    <i class="fas fa-money-bill-wave fa-lg d-block mb-1 text-success"></i>
                                    Efectivo
                                </label>
                            </div>
                            <div class="col-md-4 col-12">
                                <input type="radio" class="btn-check d-none" name="metodo_pago" id="metodo_transferencia" value="Transferencia Bancaria" autocomplete="off" onchange="toggleMetodoPagoFields()">
                                <label class="metodo-btn-box d-block mb-0" for="metodo_transferencia">
                                    <i class="fas fa-exchange-alt fa-lg d-block mb-1 text-primary"></i>
                                    Transferencia
                                </label>
                            </div>
                            <div class="col-md-4 col-12">
                                <input type="radio" class="btn-check d-none" name="metodo_pago" id="metodo_deposito" value="Depósito Bancario" autocomplete="off" onchange="toggleMetodoPagoFields()">
                                <label class="metodo-btn-box d-block mb-0" for="metodo_deposito">
                                    <i class="fas fa-university fa-lg d-block mb-1 text-dark"></i>
                                    Depósito
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- CAMPOS BANCARIOS CONDICIONALES --}}
                    <div id="campos_transferencia" style="display: none; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-7">
                                <label for="cuenta_destino" class="form-label text-dark fw-bold small">
                                    <i class="fas fa-university text-primary me-1"></i> Banco / Cuenta Destino
                                </label>
                                <div class="input-group">
                                    <select class="form-select" id="cuenta_destino" name="cuenta_destino">
                                        <option value="">-- Seleccione Cuenta Destino --</option>
                                        @if(isset($cuentasBancarias) && $cuentasBancarias->isNotEmpty())
                                            @foreach($cuentasBancarias as $cta)
                                                <option value="{{ $cta->texto_completo }}">
                                                    {{ $cta->texto_completo }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <button type="button" class="btn btn-primary" id="btn_abrir_modal_cuenta" data-bs-toggle="modal" data-bs-target="#modalNuevaCuenta" title="Agregar Nueva Cuenta Bancaria">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label for="referencia" class="form-label text-dark fw-bold small">N° Referencia / Minuta</label>
                                <input type="text" class="form-control font-monospace" id="referencia" name="referencia" placeholder="Ej: 12345678">
                            </div>
                            <div class="col-12 mt-2">
                                <label for="ruta_recibo" class="form-label text-dark fw-bold small">
                                    <i class="fas fa-paperclip text-primary me-1"></i> Adjuntar Comprobante / Minuta <span class="text-muted fw-normal">(Opcional - Imagen o PDF)</span>
                                </label>
                                <input type="file" class="form-control form-control-sm" id="ruta_recibo" name="ruta_recibo" accept="image/*,.pdf">
                                <small class="text-muted"><i class="fas fa-info-circle text-info me-1"></i>Puede adjuntar una foto de la minuta bancaria o PDF de la transferencia. Quedará guardado en el expediente del cliente para descarga y auditoría.</small>
                            </div>
                        </div>
                    </div>

                    {{-- CAJA DE COMENTARIOS / OBSERVACIONES (OPCIONAL PARA CUALQUIER MÉTODO DE PAGO) --}}
                    <div class="mb-4">
                        <label for="comentario" class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-comment-dots me-1 text-primary"></i> Comentarios / Observaciones <span class="text-muted fw-normal">(Opcional)</span>
                        </label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="2" placeholder="Notas adicionales sobre este pago (ej: billetes de $20 entregados, realizado por familiar, etc.)"></textarea>
                    </div>

                    {{-- FECHA Y CONCEPTO --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label text-muted small fw-bold text-uppercase">Fecha de Pago</label>
                            <input type="date" class="form-control" id="fecha" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_pago" class="form-label text-muted small fw-bold text-uppercase">Concepto</label>
                            <select class="form-select" id="tipo_pago" name="tipo_pago" required>
                                <option value="Mensualidad" selected>Cuota / Mensualidad</option>
                                <option value="Extraordinario">Abono Extraordinario / Capital</option>
                            </select>
                        </div>
                    </div>

                    <div id="distribucion_preview" class="alert alert-light border small text-muted text-center mb-4" style="display: none;"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        @if($ventas->count() > 1)
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="irAlPaso(1)">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                        @else
                            <div></div>
                        @endif
                        <button type="button" class="btn btn-primary btn-lg fw-bold px-4" onclick="irAlPaso(3)">
                            Revisar y Confirmar <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                {{-- ================================================= --}}
                {{-- PASO 3: CONFIRMACIÓN Y EMISIÓN --}}
                {{-- ================================================= --}}
                <div class="wizard-pane" id="wizard-pane-3">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-gray-800 mb-1">Confirmación de Abono</h4>
                        <p class="text-muted small">Verifique el resumen antes de procesar la transacción.</p>
                    </div>

                    <div class="p-4 bg-light rounded border mb-4">
                        <div class="row g-3 align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Cliente</span>
                                <strong class="text-dark fs-5">{{ $cliente->nombres_apellidos }}</strong>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted small d-block">Monto Total a Pagar</span>
                                <h3 class="text-success fw-bold mb-0" id="resumen-monto">$0.00</h3>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small d-block mb-1">Lotes a los que se aplica el abono:</span>
                            <div class="d-flex flex-wrap gap-1" id="resumen-lotes-container"></div>
                        </div>

                        <div class="row g-2 pt-2 border-top small">
                            <div class="col-md-4">
                                <span class="text-muted">Forma de Pago:</span>
                                <strong class="text-dark ms-1" id="resumen-metodo">-</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Concepto:</span>
                                <strong class="text-dark ms-1" id="resumen-tipo">-</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Fecha:</span>
                                <strong class="text-dark ms-1" id="resumen-fecha">-</strong>
                            </div>
                        </div>

                        <div id="resumen-banco-fila" class="mt-2 pt-2 border-top small" style="display: none;">
                            <span class="text-muted">Banco / Ref:</span>
                            <strong class="text-dark ms-1" id="resumen-cuenta">-</strong> | <span class="font-monospace" id="resumen-referencia">-</span>
                        </div>

                        <div id="resumen-comentario-fila" class="mt-2 pt-2 border-top small" style="display: none;">
                            <span class="text-muted"><i class="fas fa-comment-dots text-primary me-1"></i>Comentario / Nota:</span>
                            <span class="text-dark fw-bold ms-1" id="resumen-comentario">-</span>
                        </div>

                        {{-- IMPACTO FINANCIERO SIMPLE Y DIRECTO --}}
                        <div class="mt-3 pt-3 border-top" id="resumen-saldos-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Saldo Actual:</span>
                                <strong class="text-danger small" id="resumen-saldo-actual-sel">$0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Monto a Abonar:</span>
                                <strong class="text-success small" id="resumen-monto-aplicar">- $0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark">Nuevo Saldo Pendiente:</span>
                                <strong class="fs-4 text-success fw-bold" id="resumen-saldo-posterior">$0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary px-4" onclick="irAlPaso(2)">
                            <i class="fas fa-edit me-1"></i> Modificar Datos
                        </button>
                        <button type="submit" id="btn-confirmar-guardar-abono" class="btn btn-success btn-lg fw-bold px-5 py-2 shadow">
                            <i class="fas fa-check-circle me-2"></i> Confirmar y Guardar Abono
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- SECCIÓN COLAPSABLE LIMPIA: HISTORIAL DE PAGOS --}}
    <div class="wizard-card mx-auto mb-5">
        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded border shadow-sm cursor-pointer" 
             data-bs-toggle="collapse" 
             data-bs-target="#collapseHistorial" 
             aria-expanded="false" 
             aria-controls="collapseHistorial">
            <div>
                <i class="fas fa-history text-info me-2"></i>
                <strong class="text-dark">Historial de Pagos Anteriores ({{ $todosAbonos->count() }} recibos)</strong>
            </div>
            <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted">
                Ver historial <i class="fas fa-chevron-down ms-1"></i>
            </button>
        </div>
        <div class="collapse mt-2" id="collapseHistorial">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Lote</th>
                                    <th>Monto</th>
                                    <th>Tipo</th>
                                    <th>Detalles / Observaciones</th>
                                    <th class="text-center">Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todosAbonos as $abono)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y')}}</td>
                                        <td>
                                            @if($abono->venta && $abono->venta->lotes)
                                                <span class="badge bg-secondary text-white">
                                                    {{ $abono->venta->lotes->map(fn($l) => 'Lote '.$l->numero_lote)->implode(', ') }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td><strong class="text-success">${{ number_format($abono->monto_abonado, 2) }}</strong></td>
                                        <td><small>{{ $abono->tipo_pago }}</small></td>
                                        <td>
                                            @if($abono->cuenta_destino)
                                                <div class="small text-dark"><i class="fas fa-university text-secondary me-1"></i>{{ $abono->cuenta_destino }}</div>
                                            @endif
                                            @if($abono->referencia)
                                                <small class="text-muted font-monospace"><i class="fas fa-receipt me-1"></i>Ref: {{ $abono->referencia }}</small>
                                            @endif
                                            @if($abono->comentario)
                                                <div class="mt-1">
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="fas fa-comment-dots text-primary me-1"></i>{{ $abono->comentario }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('imprimirRecibo', ['abono_id' => $abono->id_abono]) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" title="Imprimir Recibo">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @can('borrar-abonos')
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 delete-abono" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-id="{{ $abono->id_abono }}" title="Borrar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No hay abonos registrados previamente.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA AGREGAR NUEVA CUENTA BANCARIA DINÁMICAMENTE --}}
    <div class="modal fade" id="modalNuevaCuenta" tabindex="-1" aria-labelledby="modalNuevaCuentaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalNuevaCuentaLabel"><i class="fas fa-university me-2"></i> Agregar Nueva Cuenta Bancaria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formNuevaCuentaBancaria">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Complete los datos de la cuenta bancaria para registrarla y seleccionarla automáticamente.</p>
                        
                        <div class="mb-3">
                            <label for="modal_banco" class="form-label small fw-bold text-dark">Banco / Entidad Financiera</label>
                            <input type="text" class="form-control" id="modal_banco" name="banco" placeholder="Ej: Banpro, BAC, LAFISE, Ficohsa, BDF" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label for="modal_moneda" class="form-label small fw-bold text-dark">Moneda</label>
                                <select class="form-select" id="modal_moneda" name="moneda" required>
                                    <option value="$">$ (Dólares)</option>
                                    <option value="C$">C$ (Córdobas)</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <label for="modal_numero_cuenta" class="form-label small fw-bold text-dark">Número de Cuenta</label>
                                <input type="text" class="form-control font-monospace" id="modal_numero_cuenta" name="numero_cuenta" placeholder="Ej: 10021210290831" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_titular" class="form-label small fw-bold text-dark">Nombre del Titular</label>
                            <input type="text" class="form-control" id="modal_titular" name="titular" placeholder="Ej: Nombre de la persona o empresa titular" required>
                        </div>
                        <div id="modal_cuenta_error" class="alert alert-danger py-2 small" style="display: none;"></div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="btnGuardarNuevaCuenta">
                            <i class="fas fa-save me-1"></i> Guardar Cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL DE ELIMINACIÓN --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-white mb-0" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="fs-5 mb-2">¿Estás seguro de que deseas eliminar este abono?</p>
                    <p class="text-danger small mb-0">Esta acción es irreversible y recalculará el saldo del contrato.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                    <form id="form-eliminar-abono" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Sí, Eliminar Abono</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    var saldoActualVenta = {{ (float)$saldoPendiente }};
    var tieneMultiplesLotes = {{ $ventas->count() > 1 ? 'true' : 'false' }};
    var pasoActual = tieneMultiplesLotes ? 1 : 2;

    function irAlPaso(numPaso) {
        if (numPaso === 2 && tieneMultiplesLotes) {
            var chks = document.querySelectorAll('.check-lote-abono:checked');
            if (chks.length === 0) {
                alert('Por favor seleccione al menos un lote para continuar.');
                return;
            }
        }

        if (numPaso === 3) {
            var inputMonto = document.getElementById('monto');
            var montoVal = parseFloat(inputMonto ? inputMonto.value : 0) || 0;
            if (montoVal <= 0) {
                alert('Por favor ingrese un monto válido mayor a $0.00');
                if (inputMonto) inputMonto.focus();
                return;
            }

            var checkedRadio = document.querySelector('input[name="metodo_pago"]:checked');
            var metodo = checkedRadio ? checkedRadio.value : 'Efectivo';
            if (metodo !== 'Efectivo') {
                var cuentaInput = document.getElementById('cuenta_destino');
                var refInput = document.getElementById('referencia');
                if (!cuentaInput.value.trim() || !refInput.value.trim()) {
                    alert('Por favor complete el nombre/cuenta bancaria y el número de referencia.');
                    if (!cuentaInput.value.trim()) cuentaInput.focus();
                    else refInput.focus();
                    return;
                }
            }

            prepararResumenConfirmacion();
        }

        pasoActual = numPaso;

        document.querySelectorAll('.wizard-pane').forEach(p => p.classList.remove('active'));
        var paneActivo = document.getElementById('wizard-pane-' + numPaso);
        if (paneActivo) paneActivo.classList.add('active');

        actualizarBarraPasos(numPaso);
        window.scrollTo({ top: 50, behavior: 'smooth' });
    }

    function actualizarBarraPasos(numPaso) {
        if (tieneMultiplesLotes) {
            for (var i = 1; i <= 3; i++) {
                var stepNav = document.getElementById('step-nav-' + i);
                if (!stepNav) continue;
                stepNav.classList.remove('active', 'completed');
                if (i < numPaso) {
                    stepNav.classList.add('completed');
                } else if (i === numPaso) {
                    stepNav.classList.add('active');
                }
            }
        } else {
            for (var i = 2; i <= 3; i++) {
                var stepNav = document.getElementById('step-nav-' + i);
                if (!stepNav) continue;
                stepNav.classList.remove('active', 'completed');
                if (i < numPaso) {
                    stepNav.classList.add('completed');
                } else if (i === numPaso) {
                    stepNav.classList.add('active');
                }
            }
        }
    }

    function toggleLoteCard(ventaId) {
        var chk = document.getElementById('chk_venta_' + ventaId);
        if (chk) {
            chk.checked = !chk.checked;
            var card = chk.closest('.lote-item-box');
            if (card) {
                if (chk.checked) card.classList.add('selected');
                else card.classList.remove('selected');
            }
            actualizarSugerenciaMonto();
        }
    }

    function toggleMetodoPagoFields() {
        var checkedRadio = document.querySelector('input[name="metodo_pago"]:checked');
        var metodo = checkedRadio ? checkedRadio.value : 'Efectivo';
        var transferSection = document.getElementById('campos_transferencia');
        var efectivoSection = document.getElementById('campos_efectivo');
        var cuentaInput = document.getElementById('cuenta_destino');
        var refInput = document.getElementById('referencia');

        if (metodo === 'Transferencia Bancaria' || metodo === 'Depósito Bancario') {
            if (transferSection) transferSection.style.display = 'block';
            if (efectivoSection) efectivoSection.style.display = 'none';
            if (cuentaInput) cuentaInput.required = true;
            if (refInput) refInput.required = true;
        } else {
            if (transferSection) transferSection.style.display = 'none';
            if (efectivoSection) efectivoSection.style.display = 'block';
            if (cuentaInput) {
                cuentaInput.required = false;
                cuentaInput.value = '';
            }
            if (refInput) {
                refInput.required = false;
                refInput.value = '';
            }
        }
    }

    function toggleTodosLotesCheckboxes() {
        var chks = document.querySelectorAll('.check-lote-abono');
        var allChecked = Array.from(chks).every(c => c.checked);
        chks.forEach(function(c) {
            c.checked = !allChecked;
            var card = c.closest('.lote-item-box');
            if (card) {
                if (c.checked) card.classList.add('selected');
                else card.classList.remove('selected');
            }
        });
        var btn = document.getElementById('btn-toggle-todos-lotes');
        if (btn) btn.innerHTML = allChecked ? '<i class="fas fa-check-square me-1"></i> Marcar Todos (' + chks.length + ')' : '<i class="fas fa-times-circle me-1"></i> Desmarcar Todos';
        actualizarSugerenciaMonto();
    }

    function actualizarSugerenciaMonto() {
        var chks = document.querySelectorAll('.check-lote-abono:checked');
        var sumaCuotas = 0;
        chks.forEach(function(c) {
            sumaCuotas += parseFloat(c.getAttribute('data-cuota')) || 0;
        });

        if (!tieneMultiplesLotes) {
            sumaCuotas = {{ (float)$cuotaSugeridaTotal }};
        }

        var spanSugerido = document.getElementById('span-monto-sugerido');
        if (spanSugerido) {
            spanSugerido.textContent = '$' + sumaCuotas.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var spanBtnSugerir = document.getElementById('span-monto-btn-sugerir');
        if (spanBtnSugerir) {
            spanBtnSugerir.textContent = '$' + sumaCuotas.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var inputMonto = document.getElementById('monto');
        var montoActual = parseFloat(inputMonto ? inputMonto.value : 0) || 0;
        var alertaSobrepago = document.getElementById('alerta-sobrepago');
        var btnContinuar2 = document.getElementById('btn-continuar-paso-2');

        if (alertaSobrepago) {
            if (montoActual > deudaMaximaGlobal + 0.001) {
                alertaSobrepago.style.display = 'block';
                if (btnContinuar2) btnContinuar2.disabled = true;
            } else {
                alertaSobrepago.style.display = 'none';
                if (btnContinuar2) btnContinuar2.disabled = false;
            }
        }

        var preview = document.getElementById('distribucion_preview');
        
        if (preview && chks.length > 0 && montoActual > 0) {
            var distribucionHtml = '<strong><i class="fas fa-calculator me-1 text-primary"></i> Distribución:</strong> ';
            var totalSeleccionados = chks.length;
            var acum = 0;
            var items = [];
            
            chks.forEach(function(c, idx) {
                var cuotaLote = parseFloat(c.getAttribute('data-cuota')) || 0;
                var asignado = 0;
                if (idx === totalSeleccionados - 1) {
                    asignado = Math.max(0, montoActual - acum);
                } else {
                    if (sumaCuotas > 0) {
                        asignado = Math.round((montoActual * (cuotaLote / sumaCuotas)) * 100) / 100;
                    } else {
                        asignado = Math.round((montoActual / totalSeleccionados) * 100) / 100;
                    }
                    acum += asignado;
                }
                items.push(c.getAttribute('data-nombre') + ': <span class="text-success fw-bold">$' + asignado.toFixed(2) + '</span>');
            });
            distribucionHtml += items.join(' | ');
            preview.innerHTML = distribucionHtml;
            preview.style.display = 'block';
        } else if (preview) {
            preview.style.display = 'none';
        }
    }

    var deudaMaximaGlobal = {{ (float)($deudaMaximaExacta ?? $saldoPendiente) }};

    function aplicarMontoTotal() {
        var inputMonto = document.getElementById('monto');
        if (inputMonto) {
            inputMonto.value = deudaMaximaGlobal.toFixed(2);
            actualizarSugerenciaMonto();
        }
    }

    function aplicarMontoSugerido() {
        var chks = document.querySelectorAll('.check-lote-abono:checked');
        var sumaCuotas = 0;
        if (chks.length > 0) {
            chks.forEach(function(c) {
                sumaCuotas += parseFloat(c.getAttribute('data-cuota')) || 0;
            });
        } else {
            sumaCuotas = {{ (float)$cuotaSugeridaTotal }};
        }
        var inputMonto = document.getElementById('monto');
        if (inputMonto) {
            inputMonto.value = Math.min(sumaCuotas, deudaMaximaGlobal).toFixed(2);
            actualizarSugerenciaMonto();
        }
    }

    function prepararResumenConfirmacion() {
        var montoVal = parseFloat(document.getElementById('monto').value) || 0;
        var tipoSelect = document.getElementById('tipo_pago');
        var tipoVal = tipoSelect ? tipoSelect.options[tipoSelect.selectedIndex].text : 'Cuota / Mensualidad';
        var checkedRadio = document.querySelector('input[name="metodo_pago"]:checked');
        var metodoVal = checkedRadio ? checkedRadio.value : 'Efectivo';
        var fechaVal = document.getElementById('fecha').value;
        var cuentaVal = document.getElementById('cuenta_destino') ? (document.getElementById('cuenta_destino').value || '-') : '-';
        var refVal = document.getElementById('referencia') ? (document.getElementById('referencia').value || '-') : '-';

        var chksSeleccionados = document.querySelectorAll('.check-lote-abono:checked');
        var contenedorLotesModal = document.getElementById('resumen-lotes-container');
        var saldoCalculadoTotal = saldoActualVenta;

        if (chksSeleccionados.length > 0 && contenedorLotesModal) {
            contenedorLotesModal.innerHTML = '';
            var sumaCuotasSel = 0;
            var sumaSaldosSel = 0;

            chksSeleccionados.forEach(function(c) {
                sumaCuotasSel += parseFloat(c.getAttribute('data-cuota')) || 0;
                sumaSaldosSel += parseFloat(c.getAttribute('data-saldo')) || 0;
            });

            if (sumaSaldosSel > 0) {
                saldoCalculadoTotal = sumaSaldosSel;
            }

            var totalSel = chksSeleccionados.length;
            var acumAsignado = 0;

            chksSeleccionados.forEach(function(c, idx) {
                var cuotaL = parseFloat(c.getAttribute('data-cuota')) || 0;
                var nomL = c.getAttribute('data-nombre');
                var asignadoL = 0;
                if (idx === totalSel - 1) {
                    asignadoL = Math.max(0, montoVal - acumAsignado);
                } else {
                    if (sumaCuotasSel > 0) {
                        asignadoL = Math.round((montoVal * (cuotaL / sumaCuotasSel)) * 100) / 100;
                    } else {
                        asignadoL = Math.round((montoVal / totalSel) * 100) / 100;
                    }
                    acumAsignado += asignadoL;
                }

                var badgeSpan = document.createElement('span');
                badgeSpan.className = 'badge bg-dark text-white me-1 mb-1 px-2 py-1';
                badgeSpan.innerHTML = '<i class="fas fa-map-marker-alt me-1 text-warning"></i>' + nomL + ' <span class="badge bg-success ms-1">$' + asignadoL.toFixed(2) + '</span>';
                contenedorLotesModal.appendChild(badgeSpan);
            });
        }

        document.getElementById('resumen-monto').textContent = '$' + montoVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var elemSugerir = document.getElementById('resumen-monto-aplicar');
        if (elemSugerir) elemSugerir.textContent = '- $' + montoVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        var elemSaldoSel = document.getElementById('resumen-saldo-actual-sel');
        if (elemSaldoSel) elemSaldoSel.textContent = '$' + saldoCalculadoTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('resumen-tipo').textContent = tipoVal;
        document.getElementById('resumen-metodo').textContent = metodoVal;
        if (document.getElementById('resumen-fecha')) {
            document.getElementById('resumen-fecha').textContent = fechaVal;
        }

        var saldoPosterior = Math.max(0, saldoCalculadoTotal - montoVal);
        document.getElementById('resumen-saldo-posterior').textContent = '$' + saldoPosterior.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        var filaBanco = document.getElementById('resumen-banco-fila');
        if (metodoVal !== 'Efectivo') {
            document.getElementById('resumen-cuenta').textContent = cuentaVal;
            document.getElementById('resumen-referencia').textContent = refVal;
            filaBanco.style.display = 'block';
        } else {
            filaBanco.style.display = 'none';
        }

        var comVal = document.getElementById('comentario') ? document.getElementById('comentario').value.trim() : '';
        var filaComentario = document.getElementById('resumen-comentario-fila');
        if (filaComentario) {
            if (comVal) {
                document.getElementById('resumen-comentario').textContent = comVal;
                filaComentario.style.display = 'block';
            } else {
                filaComentario.style.display = 'none';
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        toggleMetodoPagoFields();
        actualizarSugerenciaMonto();

        // AJAX para registrar nueva cuenta bancaria desde el modal
        var formNuevaCuenta = document.getElementById('formNuevaCuentaBancaria');
        if (formNuevaCuenta) {
            formNuevaCuenta.addEventListener('submit', function(e) {
                e.preventDefault();
                var btnGuardar = document.getElementById('btnGuardarNuevaCuenta');
                var errorBox = document.getElementById('modal_cuenta_error');
                
                if (btnGuardar) {
                    btnGuardar.disabled = true;
                    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
                }
                if (errorBox) errorBox.style.display = 'none';

                var formData = new FormData(formNuevaCuenta);

                fetch("{{ route('api.cuentas_bancarias.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(res) {
                    return res.json().then(function(data) {
                        return { status: res.status, data: data };
                    });
                })
                .then(function(result) {
                    if (result.status === 200 && result.data.success) {
                        var cta = result.data.cuenta;
                        var selectCuenta = document.getElementById('cuenta_destino');
                        if (selectCuenta) {
                            var newOption = document.createElement('option');
                            newOption.value = cta.texto_completo;
                            newOption.text = cta.texto_completo;
                            newOption.selected = true;
                            selectCuenta.appendChild(newOption);
                        }

                        // Cerrar modal
                        var modalEl = document.getElementById('modalNuevaCuenta');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modalInstance.hide();

                        // Limpiar formulario modal
                        formNuevaCuenta.reset();
                    } else {
                        var msg = result.data.message || 'Ocurrió un error al guardar la cuenta bancaria.';
                        if (result.data.errors) {
                            msg = Object.values(result.data.errors).flat().join('<br>');
                        }
                        if (errorBox) {
                            errorBox.innerHTML = msg;
                            errorBox.style.display = 'block';
                        }
                    }
                })
                .catch(function(err) {
                    if (errorBox) {
                        errorBox.innerHTML = 'Error de conexión: ' + err.message;
                        errorBox.style.display = 'block';
                    }
                })
                .finally(function() {
                    if (btnGuardar) {
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Cuenta';
                    }
                });
            });
        }

        var formAbono = document.getElementById('form-abono');
        formAbono.addEventListener('submit', function(e) {
            var btnConfirmar = document.getElementById('btn-confirmar-guardar-abono');
            if (btnConfirmar) {
                btnConfirmar.disabled = true;
                btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando Abono...';
            }
        });

        var deleteButtons = document.querySelectorAll('.delete-abono');
        deleteButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var abonoId = this.getAttribute('data-id');
                var formEliminar = document.getElementById('form-eliminar-abono');
                formEliminar.action = '/abono/' + abonoId;
            });
        });
    });
</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endpush