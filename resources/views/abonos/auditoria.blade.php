@extends('template')

@section('titulo', 'Auditoría de Recibos Firmados')

@section('contenido')
<div class="container-fluid py-3">

    {{-- CABECERA DEL MÓDULO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 pb-2 border-bottom">
        <div>
            <h3 class="mb-1 text-gray-800 fw-bold">
                <i class="fas fa-file-signature text-primary me-2"></i> Auditoría de Recibos Firmados
            </h3>
            <p class="text-muted small mb-0">
                Control y custodia de comprobantes físicos firmados por los clientes para respaldo legal y auditoría contable.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('registro.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-users me-1"></i> Expedientes de Clientes
            </a>
            <a href="{{ route('reportes.cierre_caja') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-calculator me-1"></i> Cierre de Caja
            </a>
        </div>
    </div>

    {{-- ALERTAS DE SESIÓN --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TARJETAS KPI DE AUDITORÍA --}}
    <div class="row g-3 mb-4">
        {{-- Total Recibos --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-left: 4px solid #4e73df !important; border-radius: 10px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Total Recibos Emitidos</span>
                        <h3 class="mb-0 fw-bold text-gray-800 mt-1">{{ number_format($totalRecibos) }}</h3>
                        <small class="text-muted">Total: ${{ number_format($montoTotalAuditoria, 2) }}</small>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recibos Firmados --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-left: 4px solid #10b981 !important; border-radius: 10px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Recibos con Firma Subida</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($totalFirmados) }}</h3>
                        <small class="text-success fw-bold"><i class="fas fa-check-double me-1"></i>{{ $porcentajeCumplimiento }}% Custodiados</small>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3">
                        <i class="fas fa-file-signature"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recibos Pendientes de Firma --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-left: 4px solid #f59e0b !important; border-radius: 10px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Pendientes de Firma</span>
                        <h3 class="mb-0 fw-bold {{ $totalPendientes > 0 ? 'text-warning' : 'text-muted' }} mt-1">{{ number_format($totalPendientes) }}</h3>
                        <small class="text-muted">Requieren escaneo/foto</small>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cumplimiento Auditoría --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 h-100 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 10px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-bold small text-white-50" style="font-size: 0.75rem;">Cumplimiento Auditoría</span>
                        <h3 class="mb-0 fw-bold text-white mt-1">{{ $porcentajeCumplimiento }}%</h3>
                        <div class="progress mt-2" style="height: 6px; width: 130px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $porcentajeCumplimiento }}%;" aria-valuenow="{{ $porcentajeCumplimiento }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="p-3 rounded-3 fs-3" style="background: rgba(255,255,255,0.15);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARRA DE FILTROS Y BÚSQUEDA --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('abonos.auditoria') }}" class="row g-2 align-items-center">
                
                {{-- Selector de Estado de Firma --}}
                <div class="col-md-3 col-12">
                    <label class="form-label small fw-bold text-muted mb-1">Estado de Firma:</label>
                    <select name="estado_firma" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="todos" {{ $estadoFirma === 'todos' ? 'selected' : '' }}>Todos los Recibos ({{ $totalRecibos }})</option>
                        <option value="firmados" {{ $estadoFirma === 'firmados' ? 'selected' : '' }}>✔ Solo Firmados ({{ $totalFirmados }})</option>
                        <option value="pendientes" {{ $estadoFirma === 'pendientes' ? 'selected' : '' }}>⚠️ Solo Pendientes de Firma ({{ $totalPendientes }})</option>
                    </select>
                </div>

                {{-- Búsqueda de Texto --}}
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar Recibo o Cliente:</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="N° recibo, cliente, DNI, lote, referencia...">
                        @if($search)
                            <a href="{{ route('abonos.auditoria', ['estado_firma' => $estadoFirma]) }}" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Rango de Fechas --}}
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Desde:</label>
                    <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Hasta:</label>
                    <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control form-control-sm">
                </div>

                {{-- Botón Filtrar --}}
                <div class="col-md-1 col-12 text-end mt-md-4">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold" title="Aplicar Filtros">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA DE RECIBOS Y AUDITORÍA --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list-check text-primary me-2"></i> Listado de Recibos y Estado de Custodia Física
            </h6>
            <span class="badge bg-light text-secondary border">
                Mostrando {{ $abonos->firstItem() ?? 0 }} - {{ $abonos->lastItem() ?? 0 }} de {{ $abonos->total() }} registros
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-dark small">
                        <tr>
                            <th class="ps-3" style="width: 130px;">N° Recibo</th>
                            <th>Cliente / Exp.</th>
                            <th>Lote(s)</th>
                            <th>Fecha Pago</th>
                            <th>Concepto / Método</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center" style="width: 170px;">Estado de Firma</th>
                            <th class="text-center" style="width: 180px;">Acciones de Auditoría</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abonos as $abono)
                        <tr>
                            {{-- N° Recibo --}}
                            <td class="ps-3">
                                <strong class="text-primary font-monospace fs-6">
                                    #{{ $abono->numero_recibo_formateado }}
                                </strong>
                                @if($abono->codigo_recibo)
                                    <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $abono->codigo_recibo }}</small>
                                @endif
                            </td>

                            {{-- Cliente --}}
                            <td>
                                @if($abono->venta && $abono->venta->cliente)
                                    <a href="{{ route('registro.show', $abono->venta->cliente->id_cliente) }}" class="fw-bold text-dark text-decoration-none">
                                        {{ $abono->venta->cliente->nombres_apellidos }}
                                    </a>
                                    <div class="text-muted small">
                                        Exp: <strong>{{ $abono->venta->cliente->expediente_num ?: 'N/A' }}</strong>
                                        @if($abono->venta->cliente->dni_num)
                                            | DNI: {{ $abono->venta->cliente->dni_num }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">Cliente no vinculado</span>
                                @endif
                            </td>

                            {{-- Lotes --}}
                            <td>
                                @if($abono->venta && $abono->venta->lotes)
                                    @foreach($abono->venta->lotes as $lote)
                                        <span class="badge bg-dark text-white mb-1" style="font-size: 0.75rem;">
                                            Bloque {{ $lote->bloque->nombre ?? '' }} - Lote {{ $lote->numero_lote }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            {{-- Fecha Pago --}}
                            <td>
                                <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') }}</span>
                                @if($abono->fecha_transferencia)
                                    <br><small class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Fecha en que el cliente realizó la transferencia">
                                        <i class="fas fa-university me-1"></i>Transf: {{ \Carbon\Carbon::parse($abono->fecha_transferencia)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            {{-- Concepto y Método --}}
                            <td>
                                <span class="fw-semibold text-dark d-block small">{{ $abono->tipo_pago }}</span>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.75rem;">
                                    {{ $abono->metodo_pago ?? 'Efectivo' }}
                                </span>
                                @if($abono->referencia)
                                    <small class="text-muted font-monospace d-block" style="font-size: 0.75rem;">Ref: {{ $abono->referencia }}</small>
                                @endif
                            </td>

                            {{-- Monto --}}
                            <td class="text-end">
                                <span class="fw-bold text-success fs-6">+${{ number_format($abono->monto_abonado, 2) }}</span>
                            </td>

                            {{-- Estado de Firma --}}
                            <td class="text-center">
                                @if($abono->recibo_firmado)
                                    <span class="badge bg-success py-2 px-3 fw-bold d-inline-block shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> FIRMADO
                                    </span>
                                    @if($abono->fecha_recibo_firmado)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" title="Fecha y hora de subida">
                                            <i class="fas fa-clock me-1"></i>{{ \Carbon\Carbon::parse($abono->fecha_recibo_firmado)->format('d/m/Y h:i a') }}
                                        </small>
                                    @endif
                                    @if($abono->userReciboFirmado)
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                            Por: {{ $abono->userReciboFirmado->name }}
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark py-2 px-3 fw-bold d-inline-block shadow-sm">
                                        <i class="fas fa-exclamation-triangle me-1"></i> SIN FIRMA
                                    </span>
                                    <small class="text-danger d-block mt-1 fw-semibold" style="font-size: 0.72rem;">
                                        Pendiente de custodia
                                    </small>
                                @endif
                            </td>

                            {{-- Acciones de Auditoría --}}
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    
                                    {{-- Botón para Ver / Descargar Recibo Firmado --}}
                                    @if($abono->recibo_firmado)
                                        <a href="{{ asset('storage/' . $abono->recibo_firmado) }}" target="_blank" class="btn btn-success" title="Ver Recibo Firmado por el Cliente">
                                            <i class="fas fa-file-signature me-1"></i> Ver Firmado
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary" onclick="abrirModalSubirFirma({{ $abono->id_abono }}, '{{ $abono->numero_recibo_formateado }}', '{{ $abono->venta->cliente->nombres_apellidos ?? 'Cliente' }}', true)" title="Reemplazar archivo de firma">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminarFirma({{ $abono->id_abono }}, '{{ $abono->numero_recibo_formateado }}')" title="Eliminar archivo firmado">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @else
                                        {{-- Botón para Subir Recibo Firmado --}}
                                        <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm" onclick="abrirModalSubirFirma({{ $abono->id_abono }}, '{{ $abono->numero_recibo_formateado }}', '{{ $abono->venta->cliente->nombres_apellidos ?? 'Cliente' }}', false)" title="Subir Recibo Firmado">
                                            <i class="fas fa-upload me-1"></i> Subir Firma
                                        </button>
                                    @endif

                                    {{-- Reimprimir Recibo Original en Blanco para Firma --}}
                                    <a href="{{ route('abonos.imprimir', $abono->id_abono) }}" target="_blank" class="btn btn-outline-secondary" title="Reimprimir Recibo Original">
                                        <i class="fas fa-print"></i>
                                    </a>

                                    {{-- Comprobante bancario si existe --}}
                                    @if($abono->ruta_recibo)
                                        <a href="{{ asset('storage/' . $abono->ruta_recibo) }}" target="_blank" class="btn btn-outline-info" title="Ver Comprobante de Transferencia">
                                            <i class="fas fa-paperclip"></i>
                                        </a>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                No se encontraron recibos con los criterios de búsqueda seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($abonos->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Página {{ $abonos->currentPage() }} de {{ $abonos->lastPage() }}
                    </div>
                    <div>
                        {{ $abonos->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- MODAL PARA SUBIR / ACTUALIZAR RECIBO FIRMADO --}}
<div class="modal fade" id="modalSubirFirma" tabindex="-1" aria-labelledby="modalSubirFirmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold" id="modalSubirFirmaLabel">
                    <i class="fas fa-file-signature me-2"></i> Subir Recibo Firmado por el Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formSubirFirma" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong>Recibo N°:</strong> <span id="modalFirmaReciboNum" class="text-primary font-monospace fw-bold">-</span></div>
                        <div><strong>Cliente:</strong> <span id="modalFirmaClienteNombre" class="text-dark fw-bold">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label for="input_recibo_firmado" class="form-label fw-bold text-dark">
                            <i class="fas fa-file-upload text-primary me-1"></i> Seleccionar Archivo Escaneado o Foto <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="input_recibo_firmado" name="recibo_firmado" accept="image/*,.pdf" required>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle text-info me-1"></i>Formatos admitidos: <strong>PDF, JPG, PNG, WEBP</strong> (Máx. 15MB). Asegúrese de que la firma del cliente sea claramente legible.
                        </small>
                    </div>

                    <div id="modalFirmaAvisoReemplazo" class="alert alert-warning py-2 small" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Ya existe un archivo previo. Al subir uno nuevo, el anterior será reemplazado para este recibo.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" id="btnGuardarFirma">
                        <i class="fas fa-save me-1"></i> Guardar Recibo Firmado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- FORMULARIO OCULTO PARA ELIMINACIÓN DE RECIBO FIRMADO --}}
<form id="formEliminarFirma" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
    function abrirModalSubirFirma(idAbono, numRecibo, clienteNombre, esReemplazo) {
        var form = document.getElementById('formSubirFirma');
        form.action = "{{ url('abonos') }}/" + idAbono + "/subir-recibo-firmado";
        
        document.getElementById('modalFirmaReciboNum').innerText = '#' + numRecibo;
        document.getElementById('modalFirmaClienteNombre').innerText = clienteNombre;
        
        var aviso = document.getElementById('modalFirmaAvisoReemplazo');
        if (aviso) {
            aviso.style.display = esReemplazo ? 'block' : 'none';
        }

        var input = document.getElementById('input_recibo_firmado');
        if (input) input.value = '';

        var modalEl = document.getElementById('modalSubirFirma');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function confirmarEliminarFirma(idAbono, numRecibo) {
        if (confirm('¿Está seguro de eliminar el recibo firmado del Recibo #' + numRecibo + '?\nEl estado volverá a "SIN FIRMA".')) {
            var form = document.getElementById('formEliminarFirma');
            form.action = "{{ url('abonos') }}/" + idAbono + "/eliminar-recibo-firmado";
            form.submit();
        }
    }
</script>
@endsection
