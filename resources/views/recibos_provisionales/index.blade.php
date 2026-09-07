@extends('template')

@section('titulo', 'Recibos Provisionales (Manuales)')

@section('contenido')
<div class="container-fluid">

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-file-invoice text-warning me-2"></i> Recibos Provisionales (Manuales)
            </h1>
            <p class="text-muted mb-0 small">
                Módulo para emitir e imprimir recibos en blanco o rellenados manualmente de forma independiente, sin cálculos financieros ni QR.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalNuevoReciboProvisional">
                <i class="fas fa-plus-circle me-1"></i> Emitir Recibo Provisional
            </button>
        </div>
    </div>

    {{-- Alertas --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            @if(session('imprimir_provisional_id'))
                <a href="{{ route('recibos_provisionales.imprimir', session('imprimir_provisional_id')) }}" target="_blank" class="btn btn-sm btn-success fw-bold ms-3 shadow-sm btn-imprimir-auto-prov" data-url="{{ route('recibos_provisionales.imprimir', session('imprimir_provisional_id')) }}">
                    <i class="fas fa-print me-1"></i> Imprimir Recibo Ahora
                </a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tarjeta Principal con Filtros y Tabla --}}
    <div class="card shadow mb-4 border-warning">
        <div class="card-header bg-warning text-dark py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-list me-1"></i> Historial de Recibos Provisionales Generados ({{ $recibos->total() }})
            </h6>
        </div>
        <div class="card-body">
            {{-- Filtros de Búsqueda --}}
            <form method="GET" action="{{ route('recibos_provisionales.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Buscar por N° recibo, cliente, concepto o motivo..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="lotificacion_id" class="form-select">
                        <option value="">-- Todos los Proyectos / Lotificaciones --</option>
                        @foreach($lotificaciones as $lot)
                            <option value="{{ $lot->id }}" {{ request('lotificacion_id') == $lot->id ? 'selected' : '' }}>
                                {{ $lot->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['search', 'lotificacion_id']))
                        <a href="{{ route('recibos_provisionales.index') }}" class="btn btn-outline-secondary" title="Limpiar Filtros">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>

            {{-- Tabla de Recibos Provisionales --}}
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 120px;">N° Recibo</th>
                            <th style="width: 100px;">Fecha</th>
                            <th>Proyecto</th>
                            <th>Recibimos de (Cliente)</th>
                            <th>Monto Recibo</th>
                            <th>Monto / Abono / Saldo</th>
                            <th>Concepto</th>
                            <th>Motivo / Observación</th>
                            <th>Cajero</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recibos as $recibo)
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark border border-dark fw-bold fs-6">
                                        {{ $recibo->numero_recibo_formateado }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $recibo->fecha ? $recibo->fecha->format('d/m/Y') : '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">
                                        {{ $recibo->lotificacion->nombre ?? 'General' }}
                                    </span>
                                </td>
                                <td>
                                    @if($recibo->cliente_nombre)
                                        <strong class="text-dark">{{ $recibo->cliente_nombre }}</strong>
                                    @else
                                        <span class="text-muted fst-italic">En blanco (Manual)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($recibo->monto !== null && $recibo->monto > 0)
                                        <span class="text-success fw-bold">${{ number_format($recibo->monto, 2) }}</span>
                                    @else
                                        <span class="badge bg-secondary">Sin Monto (En Blanco)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($recibo->valor_total !== null || $recibo->total_abonado !== null)
                                        <div class="small lh-sm">
                                            <span class="text-muted">Monto:</span> <strong>${{ number_format($recibo->valor_total ?? 0, 2) }}</strong><br>
                                            <span class="text-muted">Abonado:</span> <strong class="text-primary">${{ number_format($recibo->total_abonado ?? 0, 2) }}</strong><br>
                                            <span class="text-muted">Saldo:</span> <strong class="text-danger">${{ number_format($recibo->saldo_pendiente ?? max(0, ($recibo->valor_total ?? 0) - ($recibo->total_abonado ?? 0)), 2) }}</strong>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-dark">{{ $recibo->concepto ?? '—' }}</small>
                                </td>
                                <td>
                                    @if($recibo->motivo)
                                        <small class="badge bg-light text-dark border"><i class="fas fa-info-circle text-primary me-1"></i>{{ $recibo->motivo }}</small>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted"><i class="fas fa-user-circle me-1"></i>{{ $recibo->user->name ?? 'Sistema' }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('recibos_provisionales.imprimir', $recibo->id_recibo_provisional) }}" target="_blank" class="btn btn-outline-primary" title="Imprimir Recibo">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning text-dark" onclick='abrirModalEditarRecibo(@json($recibo))' title="Editar / Corregir Recibo">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @can('borrar-clientes')
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminarRecibo({{ $recibo->id_recibo_provisional }}, '{{ $recibo->numero_recibo_formateado }}')" title="Eliminar Registro">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-file-invoice fa-3x text-warning mb-2 d-block"></i>
                                    No se han generado recibos provisionales todavía.<br>
                                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevoReciboProvisional">
                                        <i class="fas fa-plus-circle me-1"></i> Emitir el primer recibo provisional
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $recibos->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL EMITIR RECIBO PROVISIONAL --}}
<div class="modal fade" id="modalNuevoReciboProvisional" tabindex="-1" aria-labelledby="modalNuevoReciboProvisionalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-warning shadow-lg">
            <form action="{{ route('recibos_provisionales.store') }}" method="POST" id="formNuevoReciboProvisional">
                @csrf
                <div class="modal-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold" id="modalNuevoReciboProvisionalLabel">
                        <i class="fas fa-file-invoice me-2"></i> Emitir Nuevo Recibo Provisional
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="cerrarModalNuevoRecibo()" style="border: none; background: transparent; font-size: 1.6rem; line-height: 1; cursor: pointer; opacity: 0.8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-start py-2 small mb-3">
                        <i class="fas fa-exclamation-triangle fa-lg me-2 mt-1 text-dark"></i>
                        <div>
                            <strong>Modo Provisional Independiente:</strong> Este recibo genera su numeración oficial del talonario/proyecto seleccionado. Oculta todos los cálculos automáticos de deuda y código QR. Puede rellenar los datos desde aquí o imprimirlo en blanco.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Proyecto / Talonario de Lotificación: <span class="text-danger">*</span></label>
                        <select name="lotificacion_id" id="selectLotificacionModal" class="form-select" required>
                            @foreach($lotificaciones as $lot)
                                <option value="{{ $lot->id }}">
                                    {{ $lot->nombre }} (RUC: {{ $lot->ruc ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3 p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="checkDejarEnBlancoIndex" name="dejar_en_blanco" value="1" onchange="toggleCamposEnBlancoIndex(this.checked)">
                        <label class="form-check-label fw-bold text-dark" for="checkDejarEnBlancoIndex">
                            <i class="fas fa-eraser me-1 text-danger"></i> Imprimir completamente en blanco (para escribir 100% a mano con bolígrafo)
                        </label>
                    </div>

                    <div id="contenedorCamposManualesIndex">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark small">Recibimos de (Nombre del Cliente):</label>
                                <input type="text" name="cliente_nombre" id="inputNombreManualIndex" class="form-control" placeholder="Escriba el nombre o déjelo en blanco">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark small">Monto Recibido en U$ (Dólares):</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">$</span>
                                    <input type="number" step="0.01" min="0" name="monto" id="inputMontoManualIndex" class="form-control fw-bold" placeholder="0.00 (Opcional)" oninput="calcularSaldoProvisionalIndex()">
                                </div>
                                <div class="form-text small">Monto que el cliente abona hoy (impreso en la casilla POR U$).</div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark small">En concepto de:</label>
                                <input type="text" name="concepto" id="inputConceptoManualIndex" class="form-control" placeholder="Ej: Abono a Lote 15 Bloque B...">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark small">Fecha del Recibo:</label>
                                <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            {{-- Sección de Cálculos: Monto Total, Abonos Anteriores, Abono Actual y Saldo Restante --}}
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border border-warning shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                        <div class="fw-bold text-dark small">
                                            <i class="fas fa-calculator text-primary me-1"></i> Estado de Cuenta (Monto / Abonado / Saldo):
                                        </div>
                                        <span class="badge bg-primary text-white" id="badgeMontoActualInfo">
                                            <i class="fas fa-money-bill-wave me-1"></i> Abono de hoy: $0.00
                                        </span>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">1. Monto Total (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold">$</span>
                                                <input type="number" step="0.01" min="0" name="valor_total" id="inputValorTotalIndex" class="form-control fw-bold" placeholder="Ej: 9000.00" oninput="calcularSaldoProvisionalIndex()">
                                            </div>
                                            <div class="form-text small text-muted">Valor total de la venta</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">2. Abonos Anteriores (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold">$</span>
                                                <input type="number" step="0.01" min="0" name="abonos_anteriores" id="inputAbonosAnterioresIndex" class="form-control" placeholder="0.00 (Opcional)" oninput="calcularSaldoProvisionalIndex()">
                                            </div>
                                            <div class="form-text small text-muted">Historial antes de hoy</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">Total Abonado (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold text-success">$</span>
                                                <input type="number" step="0.01" min="0" name="total_abonado" id="inputTotalAbonadoIndex" class="form-control fw-bold text-success" placeholder="0.00" oninput="calcularDesdeTotalAbonadoIndex()">
                                            </div>
                                            <div class="form-text small text-muted">Anteriores + Abono de hoy</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">3. Saldo Restante (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white fw-bold text-danger">$</span>
                                                <input type="text" id="previewSaldoPendienteIndex" class="form-control bg-white fw-bold text-danger" placeholder="0.00" readonly>
                                            </div>
                                            <div class="form-text small text-muted">Monto Total - Total Abonado</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 p-2 bg-white rounded border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span class="small text-muted fw-bold"><i class="fas fa-eye me-1"></i> Vista previa de la línea en el recibo:</span>
                                        <code class="fw-bold text-dark fs-6" id="previewLineaReciboTexto">Monto: U$ 0.00. Abonado: U$ 0.00. Saldo: U$ 0.00</code>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small">Motivo / Observación interna (para registro y auditoría):</label>
                                <input type="text" name="motivo" class="form-control" placeholder="Ej: Cobro en campo manual, contingencia de sistema, etc.">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-dismiss="modal" data-bs-dismiss="modal" onclick="cerrarModalNuevoRecibo()">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                        <i class="fas fa-print me-1"></i> Guardar e Imprimir Recibo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDITAR / CORREGIR RECIBO PROVISIONAL --}}
<div class="modal fade" id="modalEditarReciboProvisional" tabindex="-1" aria-labelledby="modalEditarReciboProvisionalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-primary shadow-lg">
            <form action="" method="POST" id="formEditarReciboProvisional">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold" id="modalEditarReciboProvisionalLabel">
                        <i class="fas fa-edit me-2"></i> Editar Recibo Provisional <span id="spanNumeroReciboEdit" class="badge bg-white text-primary fs-6 ms-2"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="cerrarModalEditarRecibo()" style="border: none; background: transparent; font-size: 1.6rem; line-height: 1; cursor: pointer; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center py-2 small mb-3">
                        <i class="fas fa-info-circle fa-lg me-2 text-primary"></i>
                        <div>
                            Puede corregir cualquier dato del recibo. Al guardar, los cambios se actualizarán y podrá imprimirlo nuevamente con los datos corregidos.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Proyecto / Talonario de Lotificación: <span class="text-danger">*</span></label>
                        <select name="lotificacion_id" id="selectLotificacionModalEdit" class="form-select" required>
                            @foreach($lotificaciones as $lot)
                                <option value="{{ $lot->id }}">
                                    {{ $lot->nombre }} (RUC: {{ $lot->ruc ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3 p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="checkDejarEnBlancoEdit" name="dejar_en_blanco" value="1" onchange="toggleCamposEnBlancoEdit(this.checked)">
                        <label class="form-check-label fw-bold text-dark" for="checkDejarEnBlancoEdit">
                            <i class="fas fa-eraser me-1 text-danger"></i> Recibo completamente en blanco (para llenado manual)
                        </label>
                    </div>

                    <div id="contenedorCamposManualesEdit">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark small">Recibimos de (Nombre del Cliente):</label>
                                <input type="text" name="cliente_nombre" id="inputNombreManualEdit" class="form-control" placeholder="Escriba el nombre o déjelo en blanco">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark small">Monto Recibido en U$ (Dólares):</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">$</span>
                                    <input type="number" step="0.01" min="0" name="monto" id="inputMontoManualEdit" class="form-control fw-bold" placeholder="0.00 (Opcional)" oninput="calcularSaldoProvisionalEdit()">
                                </div>
                                <div class="form-text small">Monto que el cliente abona hoy (impreso en la casilla POR U$).</div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark small">En concepto de:</label>
                                <input type="text" name="concepto" id="inputConceptoManualEdit" class="form-control" placeholder="Ej: Abono a Lote 15 Bloque B...">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark small">Fecha del Recibo:</label>
                                <input type="date" name="fecha" id="inputFechaEdit" class="form-control" required>
                            </div>

                            {{-- Sección de Cálculos Edit --}}
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border border-primary-subtle shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                        <div class="fw-bold text-dark small">
                                            <i class="fas fa-calculator text-primary me-1"></i> Estado de Cuenta (Monto / Abonado / Saldo):
                                        </div>
                                        <span class="badge bg-primary text-white" id="badgeMontoActualInfoEdit">
                                            <i class="fas fa-money-bill-wave me-1"></i> Abono de hoy: $0.00
                                        </span>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">1. Monto Total (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold">$</span>
                                                <input type="number" step="0.01" min="0" name="valor_total" id="inputValorTotalEdit" class="form-control fw-bold" placeholder="Ej: 9000.00" oninput="calcularSaldoProvisionalEdit()">
                                            </div>
                                            <div class="form-text small text-muted">Valor total de la venta</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">2. Abonos Anteriores (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold">$</span>
                                                <input type="number" step="0.01" min="0" name="abonos_anteriores" id="inputAbonosAnterioresEdit" class="form-control" placeholder="0.00 (Opcional)" oninput="calcularSaldoProvisionalEdit()">
                                            </div>
                                            <div class="form-text small text-muted">Historial antes de hoy</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">Total Abonado (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text fw-bold text-success">$</span>
                                                <input type="number" step="0.01" min="0" name="total_abonado" id="inputTotalAbonadoEdit" class="form-control fw-bold text-success" placeholder="0.00" oninput="calcularDesdeTotalAbonadoEdit()">
                                            </div>
                                            <div class="form-text small text-muted">Anteriores + Abono de hoy</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-dark small fw-bold mb-1">3. Saldo Restante (U$):</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white fw-bold text-danger">$</span>
                                                <input type="text" id="previewSaldoPendienteEdit" class="form-control bg-white fw-bold text-danger" placeholder="0.00" readonly>
                                            </div>
                                            <div class="form-text small text-muted">Monto Total - Total Abonado</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 p-2 bg-white rounded border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span class="small text-muted fw-bold"><i class="fas fa-eye me-1"></i> Vista previa de la línea en el recibo:</span>
                                        <code class="fw-bold text-dark fs-6" id="previewLineaReciboTextoEdit">Monto: U$ 0.00. Abonado: U$ 0.00. Saldo: U$ 0.00</code>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small">Motivo / Observación interna (para registro y auditoría):</label>
                                <input type="text" name="motivo" id="inputMotivoEdit" class="form-control" placeholder="Ej: Cobro en campo manual, contingencia de sistema, etc.">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-dismiss="modal" data-bs-dismiss="modal" onclick="cerrarModalEditarRecibo()">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-save me-1"></i> Guardar Cambios e Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Formulario para Eliminar Recibo --}}
<form id="formEliminarRecibo" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function cerrarModalNuevoRecibo() {
        var modalEl = document.getElementById('modalNuevoReciboProvisional');
        if (window.bootstrap && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
        if (window.jQuery) {
            $('#modalNuevoReciboProvisional').modal('hide');
        }
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }

    function cerrarModalEditarRecibo() {
        var modalEl = document.getElementById('modalEditarReciboProvisional');
        if (window.bootstrap && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
        if (window.jQuery) {
            $('#modalEditarReciboProvisional').modal('hide');
        }
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }

    function calcularSaldoProvisionalIndex() {
        var montoRecibo = parseFloat(document.getElementById('inputMontoManualIndex').value) || 0;
        var valorTotalStr = document.getElementById('inputValorTotalIndex').value;
        var abonosAntStr = document.getElementById('inputAbonosAnterioresIndex').value;
        
        // Actualizar badge de abono de hoy
        var badge = document.getElementById('badgeMontoActualInfo');
        if (badge) {
            badge.innerHTML = '<i class="fas fa-money-bill-wave me-1"></i> Abono de hoy: $' + montoRecibo.toFixed(2);
        }

        if (valorTotalStr === '' && abonosAntStr === '' && montoRecibo === 0) {
            document.getElementById('inputTotalAbonadoIndex').value = '';
            document.getElementById('previewSaldoPendienteIndex').value = '';
            document.getElementById('previewLineaReciboTexto').innerText = 'Monto: U$ 0.00. Abonado: U$ 0.00. Saldo: U$ 0.00';
            return;
        }

        var valorTotal = parseFloat(valorTotalStr) || 0;
        var abonosAnteriores = parseFloat(abonosAntStr) || 0;
        
        // Total abonado acumulado = abonos anteriores + lo que abona hoy
        var totalAbonado = abonosAnteriores + montoRecibo;
        document.getElementById('inputTotalAbonadoIndex').value = (totalAbonado > 0 || abonosAntStr !== '' || montoRecibo > 0) ? totalAbonado.toFixed(2) : '';

        var saldo = Math.max(0, valorTotal - totalAbonado);
        document.getElementById('previewSaldoPendienteIndex').value = (valorTotal > 0 || totalAbonado > 0) ? saldo.toFixed(2) : '';

        // Formato para preview
        var vTotalFormateado = valorTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var tAbonadoFormateado = totalAbonado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var saldoFormateado = saldo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        document.getElementById('previewLineaReciboTexto').innerText = 
            'Monto: U$ ' + vTotalFormateado + '. Abonado: U$ ' + tAbonadoFormateado + '. Saldo: U$ ' + saldoFormateado;
    }

    function calcularDesdeTotalAbonadoIndex() {
        var valorTotal = parseFloat(document.getElementById('inputValorTotalIndex').value) || 0;
        var totalAbonado = parseFloat(document.getElementById('inputTotalAbonadoIndex').value) || 0;
        var montoRecibo = parseFloat(document.getElementById('inputMontoManualIndex').value) || 0;
        
        // Si el usuario edita directamente el total abonado, ajustamos los abonos anteriores
        var abonosAnteriores = Math.max(0, totalAbonado - montoRecibo);
        document.getElementById('inputAbonosAnterioresIndex').value = abonosAnteriores > 0 ? abonosAnteriores.toFixed(2) : '';

        var saldo = Math.max(0, valorTotal - totalAbonado);
        document.getElementById('previewSaldoPendienteIndex').value = saldo.toFixed(2);

        var vTotalFormateado = valorTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var tAbonadoFormateado = totalAbonado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var saldoFormateado = saldo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        document.getElementById('previewLineaReciboTexto').innerText = 
            'Monto: U$ ' + vTotalFormateado + '. Abonado: U$ ' + tAbonadoFormateado + '. Saldo: U$ ' + saldoFormateado;
    }

    function toggleCamposEnBlancoIndex(enBlanco) {
        if (enBlanco) {
            $('#inputNombreManualIndex').val('').prop('disabled', true);
            $('#inputMontoManualIndex').val('').prop('disabled', true);
            $('#inputConceptoManualIndex').val('').prop('disabled', true);
            $('#inputValorTotalIndex').val('').prop('disabled', true);
            $('#inputAbonosAnterioresIndex').val('').prop('disabled', true);
            $('#inputTotalAbonadoIndex').val('').prop('disabled', true);
            $('#previewSaldoPendienteIndex').val('').prop('disabled', true);
        } else {
            $('#inputNombreManualIndex').prop('disabled', false);
            $('#inputMontoManualIndex').prop('disabled', false);
            $('#inputConceptoManualIndex').prop('disabled', false);
            $('#inputValorTotalIndex').prop('disabled', false);
            $('#inputAbonosAnterioresIndex').prop('disabled', false);
            $('#inputTotalAbonadoIndex').prop('disabled', false);
            $('#previewSaldoPendienteIndex').prop('disabled', false);
            calcularSaldoProvisionalIndex();
        }
    }

    /* Funciones para Modal de Edición */
    function calcularSaldoProvisionalEdit() {
        var montoRecibo = parseFloat(document.getElementById('inputMontoManualEdit').value) || 0;
        var valorTotalStr = document.getElementById('inputValorTotalEdit').value;
        var abonosAntStr = document.getElementById('inputAbonosAnterioresEdit').value;
        
        var badge = document.getElementById('badgeMontoActualInfoEdit');
        if (badge) {
            badge.innerHTML = '<i class="fas fa-money-bill-wave me-1"></i> Abono de hoy: $' + montoRecibo.toFixed(2);
        }

        if (valorTotalStr === '' && abonosAntStr === '' && montoRecibo === 0) {
            document.getElementById('inputTotalAbonadoEdit').value = '';
            document.getElementById('previewSaldoPendienteEdit').value = '';
            document.getElementById('previewLineaReciboTextoEdit').innerText = 'Monto: U$ 0.00. Abonado: U$ 0.00. Saldo: U$ 0.00';
            return;
        }

        var valorTotal = parseFloat(valorTotalStr) || 0;
        var abonosAnteriores = parseFloat(abonosAntStr) || 0;
        
        var totalAbonado = abonosAnteriores + montoRecibo;
        document.getElementById('inputTotalAbonadoEdit').value = (totalAbonado > 0 || abonosAntStr !== '' || montoRecibo > 0) ? totalAbonado.toFixed(2) : '';

        var saldo = Math.max(0, valorTotal - totalAbonado);
        document.getElementById('previewSaldoPendienteEdit').value = (valorTotal > 0 || totalAbonado > 0) ? saldo.toFixed(2) : '';

        var vTotalFormateado = valorTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var tAbonadoFormateado = totalAbonado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var saldoFormateado = saldo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        document.getElementById('previewLineaReciboTextoEdit').innerText = 
            'Monto: U$ ' + vTotalFormateado + '. Abonado: U$ ' + tAbonadoFormateado + '. Saldo: U$ ' + saldoFormateado;
    }

    function calcularDesdeTotalAbonadoEdit() {
        var valorTotal = parseFloat(document.getElementById('inputValorTotalEdit').value) || 0;
        var totalAbonado = parseFloat(document.getElementById('inputTotalAbonadoEdit').value) || 0;
        var montoRecibo = parseFloat(document.getElementById('inputMontoManualEdit').value) || 0;
        
        var abonosAnteriores = Math.max(0, totalAbonado - montoRecibo);
        document.getElementById('inputAbonosAnterioresEdit').value = abonosAnteriores > 0 ? abonosAnteriores.toFixed(2) : '';

        var saldo = Math.max(0, valorTotal - totalAbonado);
        document.getElementById('previewSaldoPendienteEdit').value = saldo.toFixed(2);

        var vTotalFormateado = valorTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var tAbonadoFormateado = totalAbonado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var saldoFormateado = saldo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        document.getElementById('previewLineaReciboTextoEdit').innerText = 
            'Monto: U$ ' + vTotalFormateado + '. Abonado: U$ ' + tAbonadoFormateado + '. Saldo: U$ ' + saldoFormateado;
    }

    function toggleCamposEnBlancoEdit(enBlanco) {
        if (enBlanco) {
            $('#inputNombreManualEdit').val('').prop('disabled', true);
            $('#inputMontoManualEdit').val('').prop('disabled', true);
            $('#inputConceptoManualEdit').val('').prop('disabled', true);
            $('#inputValorTotalEdit').val('').prop('disabled', true);
            $('#inputAbonosAnterioresEdit').val('').prop('disabled', true);
            $('#inputTotalAbonadoEdit').val('').prop('disabled', true);
            $('#previewSaldoPendienteEdit').val('').prop('disabled', true);
        } else {
            $('#inputNombreManualEdit').prop('disabled', false);
            $('#inputMontoManualEdit').prop('disabled', false);
            $('#inputConceptoManualEdit').prop('disabled', false);
            $('#inputValorTotalEdit').prop('disabled', false);
            $('#inputAbonosAnterioresEdit').prop('disabled', false);
            $('#inputTotalAbonadoEdit').prop('disabled', false);
            $('#previewSaldoPendienteEdit').prop('disabled', false);
            calcularSaldoProvisionalEdit();
        }
    }

    function abrirModalEditarRecibo(recibo) {
        var form = document.getElementById('formEditarReciboProvisional');
        form.action = "{{ url('recibos-provisionales') }}/" + recibo.id_recibo_provisional;

        document.getElementById('spanNumeroReciboEdit').innerText = 'N° ' + (recibo.numero_recibo_formateado || recibo.codigo_recibo || recibo.numero_recibo);

        if (recibo.lotificacion_id) {
            $('#selectLotificacionModalEdit').val(recibo.lotificacion_id);
        }

        var esEnBlanco = !recibo.cliente_nombre && (!recibo.monto || recibo.monto == 0) && !recibo.concepto && !recibo.valor_total;
        $('#checkDejarEnBlancoEdit').prop('checked', esEnBlanco);

        $('#inputNombreManualEdit').val(recibo.cliente_nombre || '');
        $('#inputMontoManualEdit').val(recibo.monto ? parseFloat(recibo.monto).toFixed(2) : '');
        $('#inputConceptoManualEdit').val(recibo.concepto || '');
        
        // Fecha
        var fechaStr = '';
        if (recibo.fecha) {
            fechaStr = recibo.fecha.substring(0, 10);
        }
        $('#inputFechaEdit').val(fechaStr || "{{ date('Y-m-d') }}");

        $('#inputValorTotalEdit').val(recibo.valor_total ? parseFloat(recibo.valor_total).toFixed(2) : '');
        $('#inputTotalAbonadoEdit').val(recibo.total_abonado ? parseFloat(recibo.total_abonado).toFixed(2) : '');
        
        // Calcular abonos anteriores si existen
        var montoRecibo = parseFloat(recibo.monto) || 0;
        var totalAbonado = parseFloat(recibo.total_abonado) || 0;
        var abonosAnt = Math.max(0, totalAbonado - montoRecibo);
        $('#inputAbonosAnterioresEdit').val(abonosAnt > 0 ? abonosAnt.toFixed(2) : '');

        $('#inputMotivoEdit').val(recibo.motivo || '');

        toggleCamposEnBlancoEdit(esEnBlanco);
        calcularSaldoProvisionalEdit();

        if (window.jQuery) {
            $('#modalEditarReciboProvisional').modal('show');
        } else if (window.bootstrap && bootstrap.Modal) {
            var modalEl = document.getElementById('modalEditarReciboProvisional');
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function confirmarEliminarRecibo(id, numero) {
        if (confirm('¿Está seguro de que desea eliminar el registro del Recibo Provisional N° ' + numero + '?')) {
            var form = document.getElementById('formEliminarRecibo');
            form.action = "{{ url('recibos-provisionales') }}/" + id;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('imprimir_provisional_id'))
            var url = "{{ route('recibos_provisionales.imprimir', session('imprimir_provisional_id')) }}";
            window.open(url, '_blank');
        @endif
    });
</script>
@endsection
