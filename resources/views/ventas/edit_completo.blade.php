@extends('template')

@section('titulo', 'Edición Integral de Contrato y Pagos')

@section('contenido')

<style>
    .card-edit-header {
        border-radius: 12px 12px 0 0;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        padding: 1rem 1.25rem;
    }
    .form-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .table-abonos-edit th {
        background-color: #f1f5f9;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        font-weight: 700;
        vertical-align: middle;
    }
    .table-abonos-edit td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .row-abono-deleted {
        background-color: #fee2e2 !important;
        opacity: 0.6;
        text-decoration: line-through;
    }
</style>

<div class="container-fluid py-3">

    {{-- ENCABEZADO SUPERIOR --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="fas fa-edit text-warning me-2"></i> Edición Integral de Contrato y Pagos
            </h3>
            <p class="text-muted small mb-0">
                Cliente: <strong class="text-dark">{{ $cliente->nombres_apellidos }}</strong> | 
                Expediente: <strong class="text-primary">{{ $cliente->expediente_num ?: 'N/D' }}</strong> | 
                Contrato ID: <strong class="text-secondary">#{{ $venta->id_venta }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('registro.show', ['cliente' => $cliente->id_cliente, 'venta_id' => $venta->id_venta]) }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Expediente
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">
            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-1"></i> Por favor corrija los siguientes errores:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('ventas.update_completo', $venta->id_venta) }}" id="formEditCompleto">
        @csrf
        @method('PUT')

        {{-- SECCIÓN 1: DATOS DEL CLIENTE / TITULAR --}}
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="form-section-title">
                    <i class="fas fa-user-check text-primary"></i> 1. Información del Cliente / Titular
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small text-muted">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombres_apellidos" class="form-control" value="{{ old('nombres_apellidos', $cliente->nombres_apellidos) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Cédula / Identificación <span class="text-danger">*</span></label>
                        <input type="text" name="identificacion" class="form-control" value="{{ old('identificacion', $cliente->identificacion) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">N° Expediente</label>
                        <input type="text" name="expediente_num" class="form-control" value="{{ old('expediente_num', $cliente->expediente_num) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">N° Promesa de Venta (PV)</label>
                        <input type="text" name="pv_num" class="form-control" value="{{ old('pv_num', $cliente->pv_num) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Dirección de Residencia</label>
                        <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $cliente->direccion) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: DATOS DEL CONTRATO Y ASIGNACIÓN DE LOTE --}}
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="form-section-title">
                    <i class="fas fa-file-contract text-success"></i> 2. Condiciones del Contrato y Lote Asignado
                </div>
                <div class="row g-3">
                    {{-- Selección de Lote --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Proyecto / Lotificación</label>
                        <select id="selectLotificacion" class="form-select" disabled>
                            @foreach($lotificaciones as $lot)
                                <option value="{{ $lot->id }}" {{ $venta->lotificacion_id == $lot->id ? 'selected' : '' }}>
                                    {{ $lot->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Proyecto asignado al contrato</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Bloque</label>
                        <select id="selectBloque" class="form-select">
                            <option value="">-- Seleccionar Bloque --</option>
                            @foreach($bloques as $b)
                                <option value="{{ $b->id_bloque }}" {{ ($loteActual && $loteActual->id_bloque == $b->id_bloque) ? 'selected' : '' }}>
                                    Bloque {{ $b->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Lote Asignado <span class="text-danger">*</span></label>
                        <select name="id_lote" id="selectLote" class="form-select" required>
                            @if($loteActual)
                                <option value="{{ $loteActual->id_lote }}" selected>
                                    Lote {{ $loteActual->numero_lote }} (Actual - Bloque {{ $loteActual->bloque?->nombre }})
                                </option>
                            @endif
                        </select>
                        <small class="text-muted">Puedes reasignar a otro lote disponible si hubo error</small>
                    </div>

                    {{-- Parámetros Financieros --}}
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Fecha del Contrato <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_venta" class="form-control" value="{{ old('fecha_venta', $venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Precio Final ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="precio_final" id="inputPrecioFinal" class="form-control" value="{{ old('precio_final', $venta->precio_final) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Plazo (Meses) <span class="text-danger">*</span></label>
                        <input type="number" name="plazo_meses" id="inputPlazoMeses" class="form-control" value="{{ old('plazo_meses', $venta->plazo_meses) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Cuota Mensual ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="cuota_mensual" id="inputCuotaMensual" class="form-control" value="{{ old('cuota_mensual', $venta->cuota_mensual) }}" required>
                    </div>

                    {{-- Beneficiario --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Beneficiario Final (Opcional)</label>
                        <input type="text" name="beneficiario_final" class="form-control" value="{{ old('beneficiario_final', $venta->beneficiario_final) }}" placeholder="Nombre del beneficiario">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Nota / Observación del Beneficiario</label>
                        <input type="text" name="nota_beneficiario" class="form-control" value="{{ old('nota_beneficiario', $venta->nota_beneficiario) }}" placeholder="Parentesco o detalle">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 3: HISTORIAL DE ABONOS / PAGOS --}}
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="form-section-title mb-0 border-0 pb-0">
                        <i class="fas fa-hand-holding-usd text-warning"></i> 3. Historial de Abonos / Pagos Realizados
                    </div>
                    <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="agregarFilaAbono()">
                        <i class="fas fa-plus me-1"></i> Agregar Nuevo Abono
                    </button>
                </div>
                <p class="text-muted small">
                    Puedes corregir números de recibo, fechas, montos, métodos de pago, agregar pagos faltantes o marcar para eliminar pagos erróneos. Al guardar, las cuotas y saldos se recalcularán automáticamente.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-abonos-edit align-middle" id="tablaAbonos">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N° Recibo</th>
                                <th style="width: 130px;">Fecha Pago</th>
                                <th style="width: 120px;">Monto ($)</th>
                                <th style="width: 140px;">Tipo Pago</th>
                                <th style="width: 140px;">Método</th>
                                <th>Referencia / Detalle</th>
                                <th class="text-center" style="width: 80px;">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAbonos">
                            @forelse($venta->abonos as $index => $abono)
                                <tr id="row_abono_{{ $abono->id_abono }}">
                                    <td>
                                        <input type="hidden" name="abonos[{{ $index }}][id_abono]" value="{{ $abono->id_abono }}">
                                        <input type="hidden" name="abonos[{{ $index }}][eliminar]" id="eliminar_abono_{{ $abono->id_abono }}" value="0">
                                        <input type="number" name="abonos[{{ $index }}][numero_recibo]" class="form-control form-control-sm fw-bold text-center" value="{{ $abono->numero_recibo }}" required>
                                    </td>
                                    <td>
                                        <input type="date" name="abonos[{{ $index }}][fecha_pago]" class="form-control form-control-sm" value="{{ $abono->fecha_pago ? \Carbon\Carbon::parse($abono->fecha_pago)->format('Y-m-d') : '' }}" required>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="abonos[{{ $index }}][monto_abonado]" class="form-control form-control-sm fw-bold text-end" value="{{ $abono->monto_abonado }}" required>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="abonos[{{ $index }}][tipo_pago]" class="form-select form-select-sm">
                                            <option value="Cuota" {{ $abono->tipo_pago == 'Cuota' || $abono->tipo_pago == 'Mensualidad' ? 'selected' : '' }}>Cuota</option>
                                            <option value="Prima/Primer Abono" {{ $abono->tipo_pago == 'Prima/Primer Abono' || $abono->tipo_pago == 'Prima' ? 'selected' : '' }}>Prima</option>
                                            <option value="Abono Extraordinario" {{ $abono->tipo_pago == 'Abono Extraordinario' ? 'selected' : '' }}>Abono Extraordinario</option>
                                            <option value="Cancelacion" {{ $abono->tipo_pago == 'Cancelacion' ? 'selected' : '' }}>Cancelación Total</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="abonos[{{ $index }}][metodo_pago]" class="form-select form-select-sm">
                                            <option value="Efectivo" {{ in_array($abono->metodo_pago, ['Efectivo', 'efectivo', '']) ? 'selected' : '' }}>Efectivo</option>
                                            <option value="Transferencia Bancaria" {{ in_array($abono->metodo_pago, ['Transferencia Bancaria', 'Transferencia', 'transferencia']) ? 'selected' : '' }}>Transferencia</option>
                                            <option value="Depósito Bancario" {{ in_array($abono->metodo_pago, ['Depósito Bancario', 'Deposito Bancario', 'Depósito', 'Deposito']) ? 'selected' : '' }}>Depósito</option>
                                            <option value="Cheque" {{ in_array($abono->metodo_pago, ['Cheque', 'cheque']) ? 'selected' : '' }}>Cheque</option>
                                        </select>
                                        <input type="hidden" name="abonos[{{ $index }}][cuenta_destino]" value="{{ $abono->cuenta_destino }}">
                                        <input type="hidden" name="abonos[{{ $index }}][fecha_transferencia]" value="{{ $abono->fecha_transferencia }}">
                                    </td>
                                    <td>
                                        <input type="text" name="abonos[{{ $index }}][referencia]" class="form-control form-control-sm" value="{{ $abono->referencia }}" placeholder="Referencia / Observación">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Marcar para eliminar" onclick="toggleEliminarAbono({{ $abono->id_abono }})">
                                            <i class="fas fa-trash-alt" id="icon_trash_{{ $abono->id_abono }}"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="no_abonos_row">
                                    <td colspan="7" class="text-center text-muted py-3">
                                        No hay abonos registrados para este contrato. Haz clic en "Agregar Nuevo Abono" si deseas ingresar uno.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 4: JUSTIFICACIÓN DE LA MODIFICACIÓN (AUDITORÍA) --}}
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="form-section-title">
                    <i class="fas fa-clipboard-check text-info"></i> 4. Justificación / Motivo del Cambio (Auditoría)
                </div>
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">
                            Explica detalladamente por qué se está realizando esta modificación <span class="text-danger">*</span>
                        </label>
                        <textarea name="motivo_modificacion" class="form-control" rows="2" placeholder="Ejemplo: Corrección de número de recibo por error en importación de Excel / Ajuste de precio acordado con cliente" required>{{ old('motivo_modificacion') }}</textarea>
                        <small class="text-muted">Este comentario quedará registrado de forma permanente en la bitácora de auditoría del sistema.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTONES DE ACCIÓN --}}
        <div class="card shadow-sm border-0 mb-5 bg-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="{{ route('registro.show', ['cliente' => $cliente->id_cliente, 'venta_id' => $venta->id_venta]) }}" class="btn btn-light border fw-bold px-4">
                    <i class="fas fa-times me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary fw-bold px-5 py-2 shadow" id="btnGuardar">
                    <i class="fas fa-save me-2"></i> Guardar Todos los Cambios y Recalcular
                </button>
            </div>
        </div>

    </form>
</div>

{{-- SCRIPT PARA SELECTS DINÁMICOS Y GESTIÓN DE FILAS DE ABONOS --}}
<script>
    let nuevoAbonoIndex = {{ $venta->abonos->count() + 100 }};

    function toggleEliminarAbono(idAbono) {
        const row = document.getElementById('row_abono_' + idAbono);
        const inputEliminar = document.getElementById('eliminar_abono_' + idAbono);
        const icon = document.getElementById('icon_trash_' + idAbono);

        if (inputEliminar.value === '1') {
            inputEliminar.value = '0';
            row.classList.remove('row-abono-deleted');
            icon.className = 'fas fa-trash-alt';
        } else {
            inputEliminar.value = '1';
            row.classList.add('row-abono-deleted');
            icon.className = 'fas fa-undo text-success';
        }
    }

    function agregarFilaAbono() {
        const noAbonos = document.getElementById('no_abonos_row');
        if (noAbonos) noAbonos.remove();

        const tbody = document.getElementById('tbodyAbonos');
        const index = nuevoAbonoIndex++;
        const hoy = new Date().toISOString().split('T')[0];

        const tr = document.createElement('tr');
        tr.id = 'row_nuevo_' + index;
        tr.style.backgroundColor = '#ecfdf5';
        tr.innerHTML = `
            <td>
                <input type="hidden" name="abonos[${index}][eliminar]" value="0">
                <input type="number" name="abonos[${index}][numero_recibo]" class="form-control form-control-sm fw-bold text-center" placeholder="Auto o N°">
            </td>
            <td>
                <input type="date" name="abonos[${index}][fecha_pago]" class="form-control form-control-sm" value="${hoy}" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="abonos[${index}][monto_abonado]" class="form-control form-control-sm fw-bold text-end" placeholder="0.00" required>
                </div>
            </td>
            <td>
                <select name="abonos[${index}][tipo_pago]" class="form-select form-select-sm">
                    <option value="Cuota" selected>Cuota</option>
                    <option value="Prima/Primer Abono">Prima</option>
                    <option value="Abono Extraordinario">Abono Extraordinario</option>
                    <option value="Cancelacion">Cancelación Total</option>
                </select>
            </td>
            <td>
                <select name="abonos[${index}][metodo_pago]" class="form-select form-select-sm">
                    <option value="Efectivo" selected>Efectivo</option>
                    <option value="Transferencia Bancaria">Transferencia</option>
                    <option value="Depósito Bancario">Depósito</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </td>
            <td>
                <input type="text" name="abonos[${index}][referencia]" class="form-control form-control-sm" placeholder="Referencia / Detalle">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('row_nuevo_${index}').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    // Select dinámico de Lotes al cambiar Bloque
    document.addEventListener('DOMContentLoaded', function() {
        const selectBloque = document.getElementById('selectBloque');
        const selectLote = document.getElementById('selectLote');
        const loteActualId = {{ $loteActual ? $loteActual->id_lote : 'null' }};
        const loteActualNombre = "{{ $loteActual ? $loteActual->numero_lote : '' }}";

        if (selectBloque) {
            selectBloque.addEventListener('change', function() {
                const bloqueId = this.value;
                if (!bloqueId) return;

                fetch(`/api/bloques/${bloqueId}/lotes`)
                    .then(res => res.json())
                    .then(data => {
                        selectLote.innerHTML = '<option value="">-- Seleccionar Lote --</option>';
                        data.forEach(l => {
                            if (l.estado === 'Disponible' || l.id_lote == loteActualId) {
                                const opt = document.createElement('option');
                                opt.value = l.id_lote;
                                opt.textContent = `Lote ${l.numero_lote} (${l.estado})`;
                                if (l.id_lote == loteActualId) {
                                    opt.selected = true;
                                    opt.textContent = `Lote ${l.numero_lote} (Actual)`;
                                }
                                selectLote.appendChild(opt);
                            }
                        });
                    })
                    .catch(err => console.error('Error cargando lotes:', err));
            });
        }
    });
</script>

@endsection
