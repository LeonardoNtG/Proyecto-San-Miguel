@extends('template')

@section('titulo', 'Registro de Abono: ' . $cliente->nombres_apellidos)

@section('contenido')

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
    <h2 class="mb-4 text-primary">
        Registrar Abono | {{ $cliente->nombres_apellidos }} 
        <small class="text-muted fs-5 ms-3">Exp. N°: {{ $cliente->expediente_num }}</small>
    </h2>

    {{-- ================================================= --}}
    {{-- SECCIÓN SUPERIOR: RESUMEN FINANCIERO --}}
    {{-- ================================================= --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Resumen de Venta (N° PV: {{ $cliente->pv_num }})</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <p class="mb-0 text-muted">Precio Total</p>
                    <h4 class="text-info">${{ number_format($venta->precio_final, 2) }}</h4>
                </div>
                <div class="col-md-3">
                    <p class="mb-0 text-muted">Abonado Total</p>
                    <h4 class="text-success">${{ number_format($totalAbonado, 2) }}</h4>
                </div>
                <div class="col-md-3">
                    <p class="mb-0 text-muted">Saldo Restante</p>
                    <h4 class="text-danger">${{ number_format($saldoPendiente, 2) }}</h4>
                </div>
                <div class="col-md-3">
                    <p class="mb-0 text-muted">Cuotas Pendientes (Mensualidad)</p>
                    <h4 class="text-warning">{{ $cuotasPendientes }} de {{ $venta->plazo_meses }}</h4>
                </div>
            </div>
            <hr class="my-3">
            <p class="text-center mb-0">
                *Lotes:* @foreach ($detallesLotes as $detalle)
                    <span class="badge bg-white me-2 text-dark border">Bloque {{ $detalle['bloque'] }} - Lote {{ $detalle['lote'] }} ({{ number_format($detalle['area'], 2) }} m²)</span>
                @endforeach
                | Día de Pago Sugerido: El -{{ $fechaPagoTeorica }}- de cada mes.
            </p>
        </div>
    </div>

    <div class="row">
        
        {{-- SECCIÓN IZQUIERDA: HISTORIAL DE ABONOS --}}
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Historial de Pagos Recientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha Pago</th>
                                    <th>Monto</th>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th style="width: 150px;">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($venta->abonos as $abono)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y')}}</td>
                                        <td><strong class="text-success">${{ number_format($abono->monto_abonado, 2) }}</strong></td>
                                        <td>{{ $abono->tipo_pago }}</td>
                                        <td>{{ $abono->referencia }}</td>
                                        <td>
                                            <a href="{{ route('imprimirRecibo', ['abono_id' => $abono->id_abono]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimir Recibo">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            
                                            @can('borrar-abonos')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-abono" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-id="{{ $abono->id_abono }}" title="Borrar Abono">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Aún no hay abonos registrados más allá de la prima inicial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DERECHA: FORMULARIO DE NUEVO ABONO Y SUBIDA DE RECIBO --}}
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Registrar Nuevo Abono</h5>
                </div>
                <div class="card-body">
                    {{-- Formulario para registrar el abono  --}}
                    <form id="form-abono" action="{{ route('abono.store', $cliente->id_cliente) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="monto" class="form-label font-weight-bold">Monto del Abono ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white fw-bold">$</span>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-lg fw-bold text-success border-success" id="monto" name="monto_abonado" placeholder="0.00" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha" class="form-label font-weight-bold">Fecha de Pago <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" required>
                        </div>

                        <!-- Tipo de Pago -->
                        <div class="mb-3">
                            <label for="tipo_pago" class="form-label font-weight-bold">Tipo de Abono / Concepto <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo_pago') is-invalid @enderror" id="tipo_pago" name="tipo_pago" required>
                                <option value="Mensualidad" {{ old('tipo_pago') == 'Mensualidad' ? 'selected' : '' }}>Mensualidad</option>
                                <option value="Extraordinario" {{ old('tipo_pago') == 'Extraordinario' ? 'selected' : '' }}>Extraordinario / Abono a Capital</option>
                                <option value="Prima/Inicial (Ya registrada)" disabled>Prima/Inicial (Ya registrada al iniciar)</option>
                            </select>
                            @error('tipo_pago')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Método de Pago con Selector Rápido de 1 Clic -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold d-block">
                                <i class="fas fa-wallet text-primary me-1"></i> Método de Pago <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group w-100 d-flex flex-wrap shadow-sm" role="group" id="group_metodo_pago">
                                <input type="radio" class="btn-check" name="metodo_pago" id="metodo_abono_efectivo" value="Efectivo" autocomplete="off" {{ old('metodo_pago', 'Efectivo') == 'Efectivo' ? 'checked' : '' }} onchange="toggleMetodoPagoFields()">
                                <label class="btn btn-outline-success py-2 fw-bold flex-fill" for="metodo_abono_efectivo">
                                    <i class="fas fa-money-bill-wave me-1"></i> Efectivo
                                </label>

                                <input type="radio" class="btn-check" name="metodo_pago" id="metodo_abono_transferencia" value="Transferencia Bancaria" autocomplete="off" {{ old('metodo_pago') == 'Transferencia Bancaria' ? 'checked' : '' }} onchange="toggleMetodoPagoFields()">
                                <label class="btn btn-outline-primary py-2 fw-bold flex-fill" for="metodo_abono_transferencia">
                                    <i class="fas fa-exchange-alt me-1"></i> Transferencia
                                </label>

                                <input type="radio" class="btn-check" name="metodo_pago" id="metodo_abono_deposito" value="Depósito Bancario" autocomplete="off" {{ old('metodo_pago') == 'Depósito Bancario' ? 'checked' : '' }} onchange="toggleMetodoPagoFields()">
                                <label class="btn btn-outline-info py-2 fw-bold flex-fill" for="metodo_abono_deposito">
                                    <i class="fas fa-university me-1"></i> Depósito
                                </label>

                                <input type="radio" class="btn-check" name="metodo_pago" id="metodo_abono_cheque" value="Cheque" autocomplete="off" {{ old('metodo_pago') == 'Cheque' ? 'checked' : '' }} onchange="toggleMetodoPagoFields()">
                                <label class="btn btn-outline-secondary py-2 fw-bold flex-fill" for="metodo_abono_cheque">
                                    <i class="fas fa-money-check me-1"></i> Cheque
                                </label>
                            </div>
                        </div>

                        <!-- Campos extra ocultos por defecto -->
                        <div id="campos_transferencia" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;" class="mb-4">
                            <div class="mb-3">
                                <label for="cuenta_destino" class="form-label text-primary fw-bold">Nombre/Cuenta Destino</label>
                                <input type="text" class="form-control" id="cuenta_destino" name="cuenta_destino" placeholder="Ej: Banco Agrícola - Empresa SA">
                            </div>
                            <div class="mb-3">
                                <label for="referencia" class="form-label text-primary fw-bold">N° de Referencia / Comprobante</label>
                                <input type="text" class="form-control" id="referencia" name="referencia">
                            </div>
                        </div>

                        <div id="campos_efectivo" class="mb-4">
                            <label for="referencia_efectivo" class="form-label font-weight-bold">Comentarios (Opcional)</label>
                            <input type="text" class="form-control" id="referencia_efectivo" name="referencia_efectivo_coment" placeholder="Observaciones opcionales">
                        </div>
                        
                        <hr>
                        <div class="mb-4 text-center p-3 border rounded bg-light">
                            <label for="ruta_recibo" class="form-label d-block fw-bold text-secondary">Imagen del Recibo / Comprobante (Opcional)</label>
                            <input type="file" class="form-control" id="ruta_recibo" name="ruta_recibo" accept="image/*">
                            <small class="text-muted mt-2 d-block">JPG o PNG del comprobante de pago.</small>
                        </div>

                        <button type="button" id="btn-preparar-abono" class="btn btn-success btn-lg w-100 shadow-sm py-2">
                            <i class="fas fa-check-circle me-1"></i> Revisar y Guardar Abono
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================= --}}
    {{-- MODAL DE RESUMEN Y CONFIRMACIÓN CONSCIENTE DE ABONO --}}
    {{-- ================================================= --}}
    <div class="modal fade" id="modalConfirmarAbono" tabindex="-1" aria-labelledby="modalConfirmarAbonoLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalConfirmarAbonoLabel">
                        <i class="fas fa-receipt me-2"></i> Confirmación de Abono
                    </h5>
                    <button type="button" class="close text-white" id="btn-x-modal-abono" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; opacity: 1; color: #fff;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                        <div>
                            <strong>Verificación consciente de pago:</strong>
                            <div class="small">Revise detenidamente los datos antes de procesar la transacción y registrar el comprobante en el sistema.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <span class="text-secondary d-block small text-uppercase fw-bold">Cliente</span>
                                <span class="fs-5 fw-bold text-dark d-block">{{ $cliente->nombres_apellidos }}</span>
                                <span class="badge bg-secondary text-white me-1 px-2 py-1">Exp: {{ $cliente->expediente_num }}</span>
                                <span class="badge bg-primary text-white px-2 py-1">PV: {{ $cliente->pv_num }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <span class="text-secondary d-block small text-uppercase fw-bold">Inmueble / Lotes</span>
                                <div class="mt-1">
                                    @foreach ($detallesLotes as $detalle)
                                        <span class="badge bg-dark text-white me-1 mb-1 px-2 py-1">
                                            Bloque {{ $detalle['bloque'] }} - Lote {{ $detalle['lote'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-success mb-3">
                        <div class="card-body p-3 bg-light">
                            <div class="row text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <span class="text-muted small d-block text-uppercase">Monto a Ingresar</span>
                                    <span class="fs-3 fw-bold text-success" id="resumen-monto">$0.00</span>
                                </div>
                                <div class="col-md-4 border-end">
                                    <span class="text-muted small d-block text-uppercase">Concepto / Tipo</span>
                                    <span class="fs-5 fw-bold text-primary" id="resumen-tipo">-</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block text-uppercase">Método de Pago</span>
                                    <span class="fs-5 fw-bold text-dark" id="resumen-metodo">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="resumen-banco-fila" class="p-3 mb-3 bg-white rounded border border-info" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Cuenta Destino:</span>
                                <strong class="text-dark" id="resumen-cuenta">-</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">N° Referencia:</span>
                                <strong class="text-primary font-monospace" id="resumen-referencia">-</strong>
                            </div>
                        </div>
                    </div>

                    {{-- IMPACTO FINANCIERO --}}
                    <div class="p-3 bg-white rounded border mb-2">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="text-muted">Fecha del Pago:</span>
                            <strong class="text-dark" id="resumen-fecha">-</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="text-muted">Saldo Pendiente Actual:</span>
                            <strong class="text-danger">${{ number_format($saldoPendiente, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-1">
                            <span class="fw-bold text-dark">Saldo Pendiente Estimado Posterior:</span>
                            <strong class="fs-5 text-success" id="resumen-saldo-posterior">$0.00</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-top">
                    <button type="button" class="btn btn-secondary text-white px-4 fw-bold" id="btn-cancelar-modal-abono" data-bs-dismiss="modal" data-dismiss="modal">
                        <i class="fas fa-edit me-1"></i> Modificar / Volver
                    </button>
                    <button type="button" id="btn-confirmar-guardar-abono" class="btn btn-success text-white px-4 fw-bold shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> Sí, Confirmar y Guardar Abono
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE ELIMINACIÓN --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-white mb-0" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; opacity: 1; color: #fff;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="fs-5 mb-2">¿Estás seguro de que deseas eliminar este abono?</p>
                    <p class="text-danger small mb-0">Esta acción es irreversible y recalculará el saldo pendiente y las cuotas del cliente.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal" data-dismiss="modal">Cancelar</button>
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

    function toggleMetodoPagoFields() {
        var checkedRadio = document.querySelector('input[name="metodo_pago"]:checked');
        var metodo = checkedRadio ? checkedRadio.value : (document.getElementById('metodo_pago') ? document.getElementById('metodo_pago').value : 'Efectivo');
        var bancoSection = document.getElementById('banco_section');
        var cuentaInput = document.getElementById('cuenta_destino');
        var refInput = document.getElementById('referencia_bancaria');
        var labelRef = document.getElementById('label_referencia');

        if (metodo === 'Transferencia Bancaria' || metodo === 'Depósito Bancario' || metodo === 'Cheque') {
            if (bancoSection) bancoSection.style.display = 'block';
            if (cuentaInput) cuentaInput.required = true;
            if (refInput) {
                refInput.required = (metodo !== 'Cheque');
            }
            if (labelRef) labelRef.textContent = (metodo === 'Cheque') ? 'N° de Cheque (Opcional)' : 'N° de Referencia / Comprobante';
        } else {
            if (bancoSection) bancoSection.style.display = 'none';
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

    document.addEventListener("DOMContentLoaded", function () {
        toggleMetodoPagoFields();

        var formAbono = document.getElementById('form-abono');
        var modalEl = document.getElementById('modalConfirmarAbono');
        var modalInstance = new bootstrap.Modal(modalEl);

        document.getElementById('btn-preparar-abono').addEventListener('click', function(e) {
            e.preventDefault();

            if (!formAbono.checkValidity()) {
                formAbono.reportValidity();
                return;
            }

            var montoVal = parseFloat(document.getElementById('monto_abonado').value) || 0;
            var tipoVal = document.getElementById('tipo_pago').value;
            var checkedRadio = document.querySelector('input[name="metodo_pago"]:checked');
            var metodoVal = checkedRadio ? checkedRadio.value : (document.getElementById('metodo_pago') ? document.getElementById('metodo_pago').value : 'Efectivo');
            var fechaVal = document.getElementById('fecha_abono').value;
            var cuentaVal = document.getElementById('cuenta_destino').value || '-';
            var refVal = document.getElementById('referencia_bancaria').value || '-';

            if (montoVal <= 0) {
                alert('Por favor ingrese un monto de abono válido mayor a 0.');
                document.getElementById('monto_abonado').focus();
                return;
            }

            document.getElementById('resumen-monto').textContent = '$' + montoVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('resumen-tipo').textContent = (tipoVal === 'Mensualidad') ? 'Cuota / Mensualidad' : 'Abono Extraordinario';
            document.getElementById('resumen-metodo').textContent = metodoVal;
            document.getElementById('resumen-fecha').textContent = fechaVal;

            var saldoPosterior = Math.max(0, saldoActualVenta - montoVal);
            document.getElementById('resumen-saldo-posterior').textContent = '$' + saldoPosterior.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            var filaBanco = document.getElementById('resumen-banco-fila');
            if (metodoVal !== 'Efectivo') {
                document.getElementById('resumen-cuenta').textContent = cuentaVal;
                document.getElementById('resumen-referencia').textContent = refVal;
                filaBanco.style.display = 'block';
            } else {
                filaBanco.style.display = 'none';
            }

            modalInstance.show();
        });

        // Cerrar modal
        $('#btn-cancelar-modal-abono, #btn-x-modal-abono').on('click', function(e) {
            e.preventDefault();
            try { modalInstance.hide(); } catch(err) {}
            $('#modalConfirmarAbono').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        });

        document.getElementById('btn-confirmar-guardar-abono').addEventListener('click', function(e) {
            e.preventDefault();
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando Abono...';
            formAbono.submit();
        });

        // Configuración del botón eliminar abono en la tabla
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
    <script src="{{ asset('js/chartM.js') }}"></script>
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
@endpush