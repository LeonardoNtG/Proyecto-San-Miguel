@extends('template')

@section('titulo', 'Perfil de Cliente: ' . $cliente->nombres_apellidos)

@section('contenido')
 @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="text-primary">Exp. N°: {{ $cliente->expediente_num }}</h2>
            
            <div class="btn-group" role="group">
                <a href="{{ route('registro.edit', $cliente->id_cliente) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar Cliente
                </a>
                
                @if($cliente->token_seguimiento)
                <a href="{{ route('portal.estado_cuenta', $cliente->token_seguimiento) }}" target="_blank" class="btn btn-primary" title="Abrir portal del cliente">
                    <i class="fas fa-external-link-alt"></i> Portal
                </a>
                <button type="button" class="btn btn-info text-white" onclick="navigator.clipboard.writeText('{{ route('portal.estado_cuenta', $cliente->token_seguimiento) }}'); alert('¡Enlace del portal copiado al portapapeles!');" title="Copiar enlace para el cliente">
                    <i class="fas fa-copy"></i> Copiar Link
                </button>
                @endif
                
                @can('gestionar-lotificaciones')
                @if(isset($cliente->ventas) && $cliente->ventas->first() && $cliente->ventas->first()->estado_contrato !== 'Rescindido')
                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#rescindirModal">
                    <i class="fas fa-ban"></i> Rescindir Venta
                </button>
                @endif
                @endcan
    
                @can('borrar-clientes')
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash"></i> Eliminar Cliente
                </button>
                @endcan
            </div>
        </div>
    </div>
    <hr>

    @php
        // Asumimos que solo gestionamos la primera (activa) de las ventas relacionadas
        $venta = $cliente->ventas->first(); 
    @endphp

    <div class="row">
        
        {{-- SECCIÓN 1: DATOS PERSONALES --}}
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Datos Personales</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nombres:</strong> {{ $cliente->nombres_apellidos }}</p>
                    <p><strong>N° PV:</strong> {{ $cliente->pv_num }}</p>
                    <p><strong>Identificación:</strong> {{ $cliente->identificacion }}</p>
                    <p><strong>Teléfono:</strong> {{ $cliente->telefono ?? 'N/A' }}</p>
                    <p><strong>Estado Civil:</strong> {{ $cliente->estado_civil ?? 'N/A' }}</p>
                    <p><strong>Dirección:</strong> {{ $cliente->direccion ?? 'N/A' }}</p>
                    <p><strong>Registro:</strong> {{ $cliente->created_at->format('d/M/Y') }}</p>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: DETALLES DE LA VENTA ACTIVA --}}
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Detalles de la Venta </h5>
                </div>
                <div class="card-body">
                    @if($venta)
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Estado Contrato:</strong>
                                    <span class="badge bg-{{ $venta->estado_contrato == 'Vigente' ? 'success' : ($venta->estado_contrato == 'Finalizado' ? 'info' : 'danger') }} text-white">
                                        {{ $venta->estado_contrato }}
                                    </span>
                                </p>
                                <p><strong>Proyecto:</strong> <span class="badge bg-primary text-white fs-6">{{ $venta->proyecto }}</span></p>
                                <p><strong>Precio Final:</strong> ${{ number_format($venta->precio_final, 2) }}</p>
                                <p><strong>Plazo (Meses):</strong> {{ $venta->plazo_meses }}</p>
                                <p><strong>Cuota Mensual:</strong> ${{ number_format($venta->cuota_mensual, 2) }}</p>
                                <p><strong>Extensión Total:</strong> {{ $venta->extension_lote }} m²</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Lotes Vendidos ({{ $venta->total_lotes_vendidos }}):</h5>
                                <ul class="list-unstyled mt-2">
                                    @foreach ($venta->lotes as $lote)
                                        <li class="mb-2">
                                            <i class="fas fa-map-marker-alt text-primary me-1"></i>
                                            <strong>Bloque {{ $lote->bloque->nombre }}</strong>, 
                                            <strong>Lote {{ $lote->numero_lote }}</strong> 
                                            <span class="text-muted">({{ number_format($lote->area_metros, 2) }} m²)</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        <p class="text-muted">No hay una venta activa registrada para este cliente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- PLAN DE PAGOS (CUOTAS) - TIPO ACORDEÓN --}}
    @if($venta && $venta->cuotas->count())
        <div class="card shadow mb-4 border-secondary">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-3" 
                 data-bs-toggle="collapse" data-bs-target="#collapsePlanCuotas" 
                 data-toggle="collapse" data-target="#collapsePlanCuotas"
                 style="cursor: pointer;" title="Clic para expandir u ocultar el Plan de Pagos">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-alt fa-lg me-2"></i>
                    <h5 class="m-0 fw-bold">Plan de Pagos (Cuotas)</h5>
                </div>
                <div class="d-flex align-items-center flex-wrap">
                    <span class="badge bg-light text-dark fw-bold me-2 shadow-sm">{{ $venta->cuotas->count() }} Cuotas</span>
                    <span class="badge bg-success text-white fw-bold me-2 shadow-sm">
                        <i class="fas fa-check me-1"></i> {{ $venta->cuotas->where('estado', 'Pagada')->count() }} Pagadas
                    </span>
                    <span class="badge bg-warning text-dark fw-bold me-2 shadow-sm">
                        <i class="fas fa-clock me-1"></i> {{ $venta->cuotas->where('estado', 'Pendiente')->count() }} Pendientes
                    </span>
                    @if($venta->cuotas->where('estado', 'Mora')->count() > 0)
                        <span class="badge bg-danger text-white fw-bold me-2 shadow-sm">
                            <i class="fas fa-exclamation-triangle me-1"></i> {{ $venta->cuotas->where('estado', 'Mora')->count() }} en Mora
                        </span>
                    @endif
                    <span class="btn btn-sm btn-outline-light ms-2 px-2 py-1">
                        <i class="fas fa-chevron-down" id="chevronCuotas"></i>
                    </span>
                </div>
            </div>
            <div class="collapse" id="collapsePlanCuotas">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center"># Cuota</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Monto Total</th>
                                    <th>Mora</th>
                                    <th>Saldo Restante</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($venta->cuotas as $cuota)
                                    <tr class="{{ $cuota->estado === 'Pagada' ? 'table-success' : ($cuota->estado === 'Mora' ? 'table-danger' : '') }}">
                                        <td class="text-center fw-bold">{{ $cuota->numero_cuota }}</td>
                                        <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                                        <td class="fw-bold">${{ number_format($cuota->monto_total, 2) }}</td>
                                        <td>
                                            @if($cuota->mora_calculada > 0)
                                                <span class="text-danger font-weight-bold" title="Mora Calculada: ${{ number_format($cuota->mora_calculada, 2) }} | Pagada: ${{ number_format($cuota->mora_pagada, 2) }} | Exonerada: ${{ number_format($cuota->mora_exonerada, 2) }}">
                                                    ${{ number_format($cuota->mora_pendiente, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-secondary">${{ number_format($cuota->saldo_restante, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge 
                                                {{ $cuota->estado === 'Pagada' ? 'bg-success text-white' : ($cuota->estado === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger text-white') }}">
                                                {{ $cuota->estado }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($cuota->mora_pendiente > 0)
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exonerarMoraModal{{ $cuota->id_cuota }}">
                                                    <i class="fas fa-handshake"></i> Negociar Mora
                                                </button>
                                                
                                                {{-- Modal para Exonerar Mora --}}
                                                <div class="modal fade" id="exonerarMoraModal{{ $cuota->id_cuota }}" tabindex="-1" aria-labelledby="exonerarMoraModalLabel{{ $cuota->id_cuota }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="exonerarMoraModalLabel{{ $cuota->id_cuota }}">Negociar Mora - Cuota #{{ $cuota->numero_cuota }}</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('cuotas.exonerarMora', $cuota->id_cuota) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-body text-start">
                                                                    <p>Mora Pendiente Actual: <strong>${{ number_format($cuota->mora_pendiente, 2) }}</strong></p>
                                                                    <div class="mb-3">
                                                                        <label for="monto_exonerar" class="form-label font-weight-bold">Monto a Exonerar / Perdonar ($)</label>
                                                                        <input type="number" step="0.01" max="{{ $cuota->mora_pendiente }}" class="form-control" name="monto_exonerar" value="{{ $cuota->mora_pendiente }}" required>
                                                                        <small class="text-muted">Si quieres perdonar toda la mora, deja el valor por defecto. Si el cliente pagará una parte, reduce el monto a perdonar.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-danger">Confirmar Exoneración</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- HISTORIAL DE ABONOS - TIPO ACORDEÓN --}}
    @if($venta && $venta->abonos->count())
        <div class="card shadow mb-4 border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3"
                 data-bs-toggle="collapse" data-bs-target="#collapseHistorialAbonos" 
                 data-toggle="collapse" data-target="#collapseHistorialAbonos"
                 style="cursor: pointer;" title="Clic para expandir u ocultar el Historial de Pagos">
                <div class="d-flex align-items-center">
                    <i class="fas fa-receipt fa-lg me-2"></i>
                    <h5 class="m-0 fw-bold">Historial de Pagos (Abonos)</h5>
                </div>
                <div class="d-flex align-items-center flex-wrap">
                    <span class="badge bg-light text-primary fw-bold me-2 shadow-sm">{{ $venta->abonos->count() }} Recibos</span>
                    <span class="badge bg-success text-white fw-bold me-2 shadow-sm">
                        Abonado: ${{ number_format($venta->total_abonado, 2) }}
                    </span>
                    <span class="badge bg-warning text-dark fw-bold me-2 shadow-sm">
                        Deuda: ${{ number_format(max(0, $venta->precio_final - $venta->total_abonado), 2) }}
                    </span>
                    <span class="btn btn-sm btn-outline-light ms-2 px-2 py-1">
                        <i class="fas fa-chevron-down" id="chevronAbonos"></i>
                    </span>
                </div>
            </div>
            <div class="collapse show" id="collapseHistorialAbonos">
                <div class="card-body">
                    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><i class="fas fa-info-circle me-1"></i> Resumen de Cuenta:</strong> 
                            Total Venta: <strong>${{ number_format($venta->precio_final, 2) }}</strong> &nbsp;|&nbsp; 
                            Total Abonado: <strong class="text-success">${{ number_format($venta->total_abonado, 2) }}</strong> &nbsp;|&nbsp; 
                            Saldo Restante: <strong class="text-danger">${{ number_format(max(0, $venta->precio_final - $venta->total_abonado), 2) }}</strong>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto Abonado</th>
                                    <th>Concepto</th>
                                    <th>Método de Pago</th>
                                    <th>Referencia / Banco</th>
                                    <th class="text-center">Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($venta->abonos as $abono)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') }}</td>
                                    <td class="text-success fw-bold">+${{ number_format($abono->monto_abonado, 2) }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $abono->tipo_pago }}</span></td>
                                    <td>{{ $abono->metodo_pago ?? 'Efectivo' }}</td>
                                    <td>
                                        {{ $abono->referencia ?? '-' }}
                                        @if($abono->cuenta_destino)
                                            <br><small class="text-muted"><i class="fas fa-university me-1"></i>{{ $abono->cuenta_destino }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('abonos.imprimir', $abono->id_abono) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Recibo">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">No hay abonos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Modal de Confirmación de Borrado --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás ABSOLUTAMENTE SEGURO de que deseas eliminar al cliente <strong>{{ $cliente->nombres_apellidos }}</strong>?</p>
                    <p class="text-danger">Esta acción es irreversible y eliminará TODOS los registros asociados (Ventas, Abonos, y liberará los Lotes).</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('registro.destroy', $cliente->id_cliente) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Sí, Eliminar Permanentemente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Rescisión de Contrato --}}
    @if($venta && $venta->estado_contrato !== 'Rescindido')
    <div class="modal fade" id="rescindirModal" tabindex="-1" aria-labelledby="rescindirModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="rescindirModalLabel">Rescindir Contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('ventas.rescindir', $venta->id_venta) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>Atención:</strong> Seleccione los lotes que el cliente desea devolver. Si selecciona todos, el contrato completo se cancelará.
                            <div class="mt-2" id="lotes_checkbox_container">
                                @foreach($venta->lotes as $lote)
                                    <div class="form-check">
                                        <input class="form-check-input lote-rescindir-checkbox" type="checkbox" name="lotes_a_rescindir[]" value="{{ $lote->id_lote }}" id="lote_res_{{ $lote->id_lote }}" checked>
                                        <label class="form-check-label" for="lote_res_{{ $lote->id_lote }}">
                                            Lote {{ $lote->numero_lote }} (Pasará a Disponible)
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Opciones de Rescisión Parcial -->
                        <div id="opciones_rescision_parcial" style="display: none;" class="border p-3 rounded mb-3 bg-light">
                            <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Opciones de Rescisión Parcial</h6>
                            
                            <div class="mb-3">
                                <label for="nuevo_pv_num" class="form-label">Nuevo N° Promesa de Venta (Opcional):</label>
                                <input type="text" class="form-control" name="nuevo_pv_num" id="nuevo_pv_num" value="{{ $cliente->pv_num }}">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nuevo_precio_final" class="form-label">Nuevo Precio Total ($):</label>
                                    <input type="number" step="0.01" class="form-control calc-plazo" name="nuevo_precio_final" id="nuevo_precio_final">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nueva_cuota_mensual" class="form-label">Nueva Cuota Mensual ($):</label>
                                    <input type="number" step="0.01" class="form-control calc-plazo" name="nueva_cuota_mensual" id="nueva_cuota_mensual">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nuevo_plazo_meses" class="form-label">Nuevo Plazo (Meses):</label>
                                <input type="number" class="form-control" name="nuevo_plazo_meses" id="nuevo_plazo_meses">
                                <small class="text-muted">Se calcula automáticamente. Puedes modificarlo si es necesario.</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="motivo_rescision" class="form-label">Motivo de la rescisión:</label>
                            <textarea class="form-control" name="motivo_rescision" id="motivo_rescision" rows="3" required placeholder="Falta de pago, mutuo acuerdo, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Confirmar Rescisión</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
   

@endsection

@section('scripts')
   <script>
        // Logica para Rescision Parcial
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.lote-rescindir-checkbox');
            const containerParcial = document.getElementById('opciones_rescision_parcial');
            
            const inputPrecio = document.getElementById('nuevo_precio_final');
            const inputCuota = document.getElementById('nueva_cuota_mensual');
            const inputPlazo = document.getElementById('nuevo_plazo_meses');

            function checkRescissionType() {
                let total = checkboxes.length;
                let checked = document.querySelectorAll('.lote-rescindir-checkbox:checked').length;

                // Si está seleccionando ALGUNOS pero NO TODOS, es parcial
                if (checked > 0 && checked < total) {
                    containerParcial.style.display = 'block';
                    inputPrecio.required = true;
                    inputCuota.required = true;
                    inputPlazo.required = true;
                } else {
                    // Rescision total (todos) o ninguno (error form)
                    containerParcial.style.display = 'none';
                    inputPrecio.required = false;
                    inputCuota.required = false;
                    inputPlazo.required = false;
                }
            }

            checkboxes.forEach(chk => {
                chk.addEventListener('change', checkRescissionType);
            });

            // Autocalcular plazo
            function calcularPlazo() {
                let precio = parseFloat(inputPrecio.value);
                let cuota = parseFloat(inputCuota.value);
                if (precio > 0 && cuota > 0) {
                    inputPlazo.value = Math.ceil(precio / cuota);
                }
            }

            inputPrecio.addEventListener('input', calcularPlazo);
            inputCuota.addEventListener('input', calcularPlazo);

            // Animación de Chevrons en Acordeones
            $('#collapsePlanCuotas').on('show.bs.collapse', function() {
                $('#chevronCuotas').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }).on('hide.bs.collapse', function() {
                $('#chevronCuotas').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            $('#collapseHistorialAbonos').on('show.bs.collapse', function() {
                $('#chevronAbonos').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }).on('hide.bs.collapse', function() {
                $('#chevronAbonos').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });
        });
    </script>
        <script src="{{ asset('js/jqueryEM.js') }}"></script>

         <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
    
@endsection