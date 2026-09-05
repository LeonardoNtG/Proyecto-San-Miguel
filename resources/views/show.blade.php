@extends('template')

@section('titulo', 'Perfil de Cliente: ' . $cliente->nombres_apellidos)

@section('contenido')

    @if (session('imprimir_abonos'))
        <div class="alert alert-success shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 p-3 border-success">
            <div>
                <h5 class="alert-heading mb-1 fw-bold text-success"><i class="fas fa-check-circle me-1"></i> ¡Abono registrado con éxito!</h5>
                <p class="mb-0 text-dark">{{ session('success') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(session('imprimir_abonos') as $abonoId)
                    <a href="{{ route('imprimirRecibo', ['abono_id' => $abonoId]) }}" target="_blank" class="btn btn-success btn-lg fw-bold shadow-sm px-3 btn-imprimir-auto" data-url="{{ route('imprimirRecibo', ['abono_id' => $abonoId]) }}">
                        <i class="fas fa-print me-1"></i> Imprimir Recibo #{{ $abonoId }}
                    </a>
                @endforeach
            </div>
        </div>
    @elseif (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $venta = $ventaActual ?? ($cliente->ventas->firstWhere('estado_contrato', 'Vigente') ?? $cliente->ventas->first());
        $tieneMultiplesContratos = $cliente->ventas->count() > 1;
        $tieneOtrosContratos = false;
        $otrosContratosActivos = collect();
        if ($venta) {
            $otrosContratosActivos = $cliente->ventas->where('id_venta', '!=', $venta->id_venta)->where('estado_contrato', 'Vigente');
            $tieneOtrosContratos = $otrosContratosActivos->count() > 0;
        }
    @endphp

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
                
                @if($venta && $venta->estado_contrato !== 'Rescindido')
                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#rescindirModal">
                    <i class="fas fa-ban"></i> Rescindir Venta
                </button>
                @endif
    
                @can('borrar-clientes')
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash"></i> Eliminar Cliente
                </button>
                @endcan
            </div>
        </div>
    </div>
    <hr>

    @if($tieneMultiplesContratos)
    {{-- BARRA SELECTORA DE CONTRATOS (cuando el cliente tiene varios lotes independientes) --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-layer-group me-1"></i> Este cliente posee {{ $cliente->ventas->count() }} Contratos Independientes — Seleccione para ver su estado y plan de pagos:
            </h6>
        </div>
        <div class="card-body p-2 bg-light">
            <div class="row g-2">
                @foreach($cliente->ventas as $v)
                    @php
                        $lotesV = $v->lotes;
                        $nombreLotes = $lotesV->map(fn($l) => 'Bloque '.($l->bloque->nombre ?? '').' - Lote '.$l->numero_lote)->implode(', ');
                        $esActual = ($venta && $venta->id_venta == $v->id_venta);
                        $enMora = $v->cuotas->where('estado', 'Mora')->count() > 0;
                    @endphp
                    <div class="col-md-4">
                        <a href="{{ route('registro.show', [$cliente->id_cliente, 'venta_id' => $v->id_venta]) }}" class="text-decoration-none">
                            <div class="p-2 rounded border {{ $esActual ? 'border-primary bg-primary text-white shadow' : 'border-secondary bg-white text-dark' }} transition-all">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="fs-6">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $nombreLotes ?: 'Contrato #'.$v->id_venta }}
                                    </strong>
                                    @if($v->estado_contrato === 'Rescindido')
                                        <span class="badge {{ $esActual ? 'bg-light text-dark' : 'bg-secondary text-white' }}">Rescindido</span>
                                    @elseif($enMora)
                                        <span class="badge bg-danger">Mora</span>
                                    @else
                                        <span class="badge {{ $esActual ? 'bg-light text-primary' : 'bg-success text-white' }}">Vigente</span>
                                    @endif
                                </div>
                                @if($v->beneficiario_final)
                                    <div class="small {{ $esActual ? 'text-white-50' : 'text-muted' }} mt-1">
                                        <i class="fas fa-user-tie me-1"></i> {{ $v->beneficiario_final }}
                                    </div>
                                @endif
                                <div class="small mt-1 {{ $esActual ? 'text-white' : 'text-muted' }} d-flex justify-content-between">
                                    <span>${{ number_format($v->cuota_mensual, 2) }}/mes</span>
                                    <span>${{ number_format($v->precio_final, 2) }} total</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

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
                    <p><strong>Registro:</strong> {{ $cliente->created_at ? $cliente->created_at->format('d/M/Y') : ($cliente->ventas->first()?->fecha_venta ? \Carbon\Carbon::parse($cliente->ventas->first()->fecha_venta)->format('d/M/Y') : 'N/A') }}</p>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: DETALLES DE LA VENTA ACTIVA --}}
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="m-0">
                        Detalles del Contrato
                        @if($venta && $venta->lotes->count() > 0)
                            <span class="badge bg-light text-dark ms-2">
                                {{ $venta->lotes->map(fn($l) => 'Lote '.$l->numero_lote)->implode(', ') }}
                            </span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @if($venta)
                            <a href="{{ route('reportes.promesa_venta.imprimir', $venta->id_venta) }}" target="_blank" class="btn btn-sm btn-light text-success fw-bold shadow-sm" title="Imprimir Ficha Técnica para Notario / Abogado">
                                <i class="fas fa-file-contract me-1"></i> Ficha Promesa de Venta
                            </a>
                        @endif
                        @if($tieneMultiplesContratos)
                            <span class="badge bg-warning text-dark">{{ $cliente->ventas->count() }} Contratos en total</span>
                        @endif
                    </div>
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
                                <p><strong>Cuota Mensual:</strong> ${{ number_format($venta->cuota_mensual, 2) }}
                                    @if($venta->total_lotes_vendidos > 1)
                                        <span class="badge bg-light text-dark border ms-1">
                                            ${{ number_format(max(0, $venta->cuota_mensual / max(1, $venta->total_lotes_vendidos)), 2) }} / lote
                                        </span>
                                    @endif
                                </p>
                                <p><strong>Extensión Total:</strong> {{ $venta->extension_lote }} m²</p>
                                @if($venta->beneficiario_final)
                                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0">
                                        <small class="fw-bold d-block"><i class="fas fa-user-tie me-1"></i> Beneficiario Final / Futuro Titular:</small>
                                        <strong>{{ $venta->beneficiario_final }}</strong>
                                        @if($venta->nota_beneficiario)
                                            <br><small class="text-muted">{{ $venta->nota_beneficiario }}</small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h5>Lotes Activos en Contrato ({{ $venta->lotes->count() }}):</h5>
                                <ul class="list-unstyled mt-2 mb-2">
                                    @forelse ($venta->lotes as $lote)
                                        <li class="mb-2">
                                            <i class="fas fa-map-marker-alt text-primary me-1"></i>
                                            <strong>Bloque {{ $lote->bloque ? $lote->bloque->nombre : '' }}</strong>, 
                                            <strong>Lote {{ $lote->numero_lote }}</strong> 
                                            <span class="text-muted">({{ number_format($lote->area_metros, 2) }} m²)</span>
                                        </li>
                                    @empty
                                        <li class="text-muted small">No hay lotes activos en este contrato.</li>
                                    @endforelse
                                </ul>

                                @if($venta->lotesRescindidos && $venta->lotesRescindidos->count() > 0)
                                    <div class="mt-2 pt-2 border-top">
                                        <small class="text-danger fw-bold d-block mb-1">
                                            <i class="fas fa-undo me-1"></i> Lotes Rescindidos / Devueltos ({{ $venta->lotesRescindidos->count() }}):
                                        </small>
                                        @foreach($venta->lotesRescindidos as $loteRes)
                                            <span class="badge bg-light text-muted border text-decoration-line-through me-1 mb-1" title="Lote devuelto a disponible">
                                                Bloque {{ $loteRes->bloque ? $loteRes->bloque->nombre : '' }} - Lote {{ $loteRes->numero_lote }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted">No hay una venta activa registrada para este cliente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    


    {{-- HISTORIAL DE ABONOS - TIPO ACORDEÓN --}}
    @if($venta && $venta->abonos->count())
        <div class="card shadow mb-4 border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3"
                 id="headerHistorialAbonos"
                 style="cursor: pointer; user-select: none;" title="Clic para expandir u ocultar el Historial de Pagos">
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
                        <i class="fas fa-chevron-up" id="chevronAbonos"></i>
                    </span>
                </div>
            </div>
            <div id="bodyHistorialAbonos" style="display: block;">
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
                                    <th class="text-center">Soporte Bancario</th>
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
                                        @if($abono->ruta_recibo)
                                            <a href="{{ asset('storage/' . $abono->ruta_recibo) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver / Descargar Comprobante Bancario">
                                                <i class="fas fa-paperclip me-1"></i> Ver Comprobante
                                            </a>
                                        @else
                                            <span class="text-muted small">Sin adjunto</span>
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
                                    <td colspan="7" class="text-center py-3 text-muted">No hay abonos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- HISTORIAL DE MODIFICACIONES Y CESIONES - TIPO ACORDEÓN --}}
    <div class="card shadow mb-4 border-info">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3"
             id="headerHistorialModificaciones"
             style="cursor: pointer; user-select: none;" title="Clic para expandir u ocultar el Historial de Modificaciones">
            <div class="d-flex align-items-center">
                <i class="fas fa-history fa-lg text-warning me-2"></i>
                <h5 class="m-0 fw-bold">Historial de Modificaciones y Cesiones</h5>
            </div>
            <div class="d-flex align-items-center flex-wrap">
                <span class="badge bg-warning text-dark fw-bold me-2 shadow-sm">
                    {{ isset($historialModificaciones) ? $historialModificaciones->count() : 0 }} Registros
                </span>
                <span class="btn btn-sm btn-outline-light ms-2 px-2 py-1">
                    <i class="fas {{ (isset($historialModificaciones) && $historialModificaciones->count() > 0) ? 'fa-chevron-up' : 'fa-chevron-down' }}" id="chevronModificaciones"></i>
                </span>
            </div>
        </div>
        <div id="bodyHistorialModificaciones" style="display: {{ (isset($historialModificaciones) && $historialModificaciones->count() > 0) ? 'block' : 'none' }};">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 15%;">Fecha y Hora</th>
                                <th style="width: 15%;">Responsable</th>
                                <th style="width: 20%;">Tipo de Acción</th>
                                <th>Detalle del Cambio (Antes ➔ Después)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historialModificaciones ?? [] as $historial)
                            <tr>
                                <td>
                                    <i class="far fa-clock text-secondary me-1"></i>
                                    {{ $historial->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-white">
                                        <i class="fas fa-user me-1"></i>{{ $historial->user ? $historial->user->name : 'Sistema' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ str_contains($historial->accion, 'Cesión') ? 'bg-primary text-white' : 'bg-info text-dark' }}">
                                        {{ $historial->accion }}
                                    </span>
                                </td>
                                <td>
                                    {!! $historial->detalles !!}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    <i class="fas fa-info-circle me-1"></i> No se registran modificaciones ni cesiones en este expediente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- SECCIÓN 5: HISTORIAL DE RESCISIONES Y DESISTIMIENTOS --}}
    @if(isset($cliente->rescisiones) && $cliente->rescisiones->count() > 0)
    <div class="card shadow mb-4 border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center cursor-pointer" id="headerHistorialRescisiones" style="cursor: pointer;">
            <div class="d-flex align-items-center">
                <i class="fas fa-undo-alt fa-lg text-warning me-2"></i>
                <h5 class="m-0 fw-bold">Historial de Rescisiones y Desistimientos de Lotes</h5>
            </div>
            <div class="d-flex align-items-center flex-wrap">
                <span class="badge bg-white text-danger fw-bold me-2 shadow-sm">
                    {{ $cliente->rescisiones->count() }} Lotes Liberados
                </span>
                <span class="btn btn-sm btn-outline-light ms-2 px-2 py-1">
                    <i class="fas fa-chevron-up" id="chevronRescisiones"></i>
                </span>
            </div>
        </div>
        <div id="bodyHistorialRescisiones" style="display: block;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 13%;">Fecha y Hora</th>
                                <th style="width: 10%;">Tipo</th>
                                <th style="width: 20%;">Lotes Desistidos (Disponible)</th>
                                <th style="width: 15%;">Lotes Conservados</th>
                                <th style="width: 18%;">Destino de lo Abonado</th>
                                <th>Comentario / Motivo</th>
                                <th style="width: 10%;">Responsable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cliente->rescisiones as $r)
                            <tr>
                                <td class="small">
                                    <i class="far fa-clock text-secondary me-1"></i>
                                    {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y h:i A') }}
                                </td>
                                <td>
                                    <span class="badge {{ $r->tipo == 'Parcial' ? 'bg-warning text-dark' : 'bg-danger' }}">
                                        {{ $r->tipo }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border border-danger fw-bold py-1 px-2">
                                        <i class="fas fa-undo me-1"></i> {{ $r->lotes_afectados }}
                                    </span>
                                    <br><small class="text-success"><i class="fas fa-check-circle me-1"></i> Pasa a Disponible</small>
                                </td>
                                <td class="small">
                                    {{ $r->lotes_conservados ?: '—' }}
                                </td>
                                <td>
                                    @if($r->destino_abonos == 'acreditar_otro_lote')
                                        <span class="badge bg-success">Acreditado a lote conservado</span>
                                        <div class="small fw-bold text-success mt-1">${{ number_format($r->monto_transferido, 2) }}</div>
                                    @elseif($r->destino_abonos == 'devolucion_efectivo')
                                        <span class="badge bg-danger">Devolución en efectivo</span>
                                        <div class="small fw-bold text-danger mt-1">${{ number_format($r->monto_devuelto, 2) }}</div>
                                    @else
                                        <span class="badge bg-secondary">Sin devolución</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="p-2 bg-light rounded border">
                                        {{ $r->comentario }}
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    {{ $r->user ? $r->user->name : 'Sistema' }}
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

    {{-- Modal de Rescisión / Desistimiento de Lotes --}}
    @if($venta && $venta->estado_contrato !== 'Rescindido')
    <div class="modal fade" id="rescindirModal" tabindex="-1" aria-labelledby="rescindirModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="rescindirModalLabel">
                        <i class="fas fa-undo-alt me-2"></i> Rescindir / Desistir de Lotes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('ventas.rescindir', $venta->id_venta) }}" method="POST" id="form-rescindir-venta">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle me-1"></i> Selección de Lotes a Desistir / Devolver:</strong>
                            <p class="small mb-2 text-dark">
                                Marque los lotes de los cuales el cliente desea desistir. 
                                <strong>El lote pasará automáticamente a estado "Disponible" (libre)</strong> en el inventario para poder venderse a una nueva persona.
                            </p>
                            <div class="mt-2" id="lotes_checkbox_container">
                                @foreach($venta->lotes as $lote)
                                    <div class="form-check mb-2 p-2 bg-white rounded border">
                                        <input class="form-check-input lote-rescindir-checkbox ms-1 me-2" 
                                               type="checkbox" 
                                               name="lotes_a_rescindir[]" 
                                               value="{{ $lote->id_lote }}" 
                                               id="lote_res_{{ $lote->id_lote }}" 
                                               data-area="{{ $lote->area_metros }}"
                                               checked>
                                        <label class="form-check-label fw-bold text-dark" for="lote_res_{{ $lote->id_lote }}">
                                            Bloque {{ $lote->bloque ? $lote->bloque->nombre : '' }} - Lote {{ $lote->numero_lote }}
                                            <span class="text-muted fw-normal">({{ number_format($lote->area_metros, 2) }} m²)</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Destino del dinero abonado --}}
                        @php
                            $otrosContratosActivos = $cliente->ventas->where('id_venta', '!=', $venta->id_venta)->where('estado_contrato', 'Vigente');
                            $tieneOtrosContratos = $otrosContratosActivos->count() > 0;
                            $totalLotesEnVenta = $venta->lotes->count();
                            // ¿Puede acreditar a otro lote inicialmente? Solo si este contrato tiene más de 1 lote (rescisión parcial) O si el cliente tiene otro contrato activo vigente!
                            $puedeAcreditarInicial = ($totalLotesEnVenta > 1) || $tieneOtrosContratos;
                        @endphp
                        <div class="card bg-light border p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="fas fa-coins text-warning me-1"></i> Destino del Dinero Abonado por el Lote:
                            </h6>
                            <p class="small text-muted mb-2">El total abonado a este contrato asciende a: <strong>${{ number_format($venta->abonos()->where('monto_abonado', '>', 0)->sum('monto_abonado'), 2) }}</strong></p>
                            
                            {{-- Opción: Acreditar a lote conservado (solo si conserva algún lote o tiene otro contrato) --}}
                            <div class="form-check mb-2 p-2 bg-white rounded border" id="contenedor_opcion_acreditar" style="{{ $puedeAcreditarInicial ? '' : 'display: none !important;' }}">
                                <input class="form-check-input destino-abonos-radio ms-1 me-2" type="radio" name="destino_abonos" id="dest_acreditar" value="acreditar_otro_lote" {{ $puedeAcreditarInicial ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-success" for="dest_acreditar">
                                    <i class="fas fa-arrow-circle-right me-1"></i> Acreditar al lote / contrato que conserva
                                </label>
                                <div class="small text-muted ps-4">El dinero pagado por el lote desistido reduce la deuda pendiente del lote que el cliente mantiene.</div>
                            </div>

                            @if($tieneOtrosContratos)
                            <div id="selector_contrato_destino" class="ps-4 mb-2 p-2 bg-white rounded border border-primary" style="display:none;">
                                <label class="small fw-bold text-primary"><i class="fas fa-link me-1"></i> En caso de rescisión total, transferir crédito a:</label>
                                <select name="id_venta_destino" class="form-select form-select-sm mt-1">
                                    @foreach($otrosContratosActivos as $otraVenta)
                                        @php
                                            $lotesOtra = $otraVenta->lotes->map(fn($l) => ($l->bloque ? 'Blq '.$l->bloque->nombre.' - ' : '').'Lote '.$l->numero_lote)->implode(', ');
                                        @endphp
                                        <option value="{{ $otraVenta->id_venta }}">Contrato #{{ $otraVenta->id_venta }} ({{ $lotesOtra ?: 'Sin lotes' }}) — Saldo actual: ${{ number_format(max(0, $otraVenta->precio_final - $otraVenta->abonos()->sum('monto_abonado')), 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="form-check mb-2 p-2 bg-white rounded border" id="contenedor_opcion_efectivo">
                                <input class="form-check-input destino-abonos-radio ms-1 me-2" type="radio" name="destino_abonos" id="dest_efectivo" value="devolucion_efectivo" {{ !$puedeAcreditarInicial ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-danger" for="dest_efectivo">
                                    <i class="fas fa-hand-holding-usd me-1"></i> Liquidar y devolver en efectivo al cliente
                                </label>
                                <div class="small text-muted ps-4">El dinero no se transfiere a ningún lote. Se registra una liquidación y salida de caja en el sistema.</div>
                            </div>

                            <div class="form-check p-2 bg-white rounded border">
                                <input class="form-check-input destino-abonos-radio ms-1 me-2" type="radio" name="destino_abonos" id="dest_sin_devolucion" value="sin_devolucion">
                                <label class="form-check-label fw-bold text-secondary" for="dest_sin_devolucion">
                                    <i class="fas fa-ban me-1"></i> Sin devolución (Penalización / Cláusula de contrato)
                                </label>
                                <div class="small text-muted ps-4">El lote se libera a disponible sin crédito ni devolución económica.</div>
                            </div>
                        </div>

                        <!-- Opciones de Rescisión Parcial -->
                        <div id="opciones_rescision_parcial" style="display: none;" class="border p-3 rounded mb-3 bg-light shadow-sm">
                            <h6 class="text-primary mb-2 fw-bold"><i class="fas fa-calculator me-1"></i> Rescisión Parcial: Recálculo Proporcional</h6>
                            
                            <div class="alert alert-info py-2 px-3 mb-3 small" id="resumen_proporcional_info">
                                <!-- Actualizado automáticamente por JS -->
                            </div>
                            
                            <div class="mb-3">
                                <label for="nuevo_pv_num" class="form-label font-weight-bold">Nuevo N° Promesa de Venta (Opcional):</label>
                                <input type="text" class="form-control" name="nuevo_pv_num" id="nuevo_pv_num" value="{{ $cliente->pv_num }}">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nuevo_precio_final" class="form-label font-weight-bold">Nuevo Precio Total ($):</label>
                                    <input type="number" step="0.01" min="0" class="form-control calc-plazo font-weight-bold" name="nuevo_precio_final" id="nuevo_precio_final">
                                    <small class="text-muted">Proporcional al número de lotes activos.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nueva_cuota_mensual" class="form-label font-weight-bold">Nueva Cuota Mensual ($):</label>
                                    <input type="number" step="0.01" min="0" class="form-control calc-plazo font-weight-bold text-success" name="nueva_cuota_mensual" id="nueva_cuota_mensual">
                                    <small class="text-muted">Cuota dividida equitativamente entre los lotes.</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nuevo_plazo_meses" class="form-label font-weight-bold">Nuevo Plazo (Meses):</label>
                                <input type="number" min="1" class="form-control" name="nuevo_plazo_meses" id="nuevo_plazo_meses">
                                <small class="text-muted">Se calcula automáticamente: Precio / Cuota.</small>
                            </div>
                        </div>

                        {{-- Comentario Obligatorio --}}
                        <div class="mb-3">
                            <label for="motivo_rescision" class="form-label fw-bold text-dark">
                                <i class="fas fa-comment-dots text-primary me-1"></i> Comentario / Justificación de la Rescisión: <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="motivo_rescision" id="motivo_rescision" rows="3" required minlength="5" placeholder="Explique claramente el motivo del desistimiento, los acuerdos alcanzados con el cliente y destino de los fondos..."></textarea>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Este comentario quedará registrado permanentemente en el historial de rescisiones y auditoría.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold px-4">
                            <i class="fas fa-check me-1"></i> Confirmar Rescisión y Liberar Lote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
   

@endsection

@section('scripts')
   <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($venta && $venta->estado_contrato !== 'Rescindido')
            // Logica para Rescisión de Lotes
            const checkboxes = document.querySelectorAll('.lote-rescindir-checkbox');
            const containerParcial = document.getElementById('opciones_rescision_parcial');
            
            const inputPrecio = document.getElementById('nuevo_precio_final');
            const inputCuota = document.getElementById('nueva_cuota_mensual');
            const inputPlazo = document.getElementById('nuevo_plazo_meses');

            const totalLotes = {{ $venta ? $venta->lotes->count() : 0 }};
            const precioTotalOriginal = {{ $venta ? (float)$venta->precio_final : 0 }};
            const cuotaOriginal = {{ $venta ? (float)$venta->cuota_mensual : 0 }};
            const plazoOriginal = {{ $venta ? (int)$venta->plazo_meses : 0 }};
            const cuotaPorLoteOriginal = totalLotes > 0 ? (cuotaOriginal / totalLotes) : 0;
            const precioPorLoteOriginal = totalLotes > 0 ? (precioTotalOriginal / totalLotes) : 0;

            function checkRescissionType() {
                let checkedCount = document.querySelectorAll('.lote-rescindir-checkbox:checked').length;
                let conservados = totalLotes - checkedCount;

                // Si se devuelven algunos pero NO todos (conserva al menos 1 lote)
                if (checkedCount > 0 && conservados > 0) {
                    if (containerParcial) containerParcial.style.display = 'block';
                    if (inputPrecio) inputPrecio.required = true;
                    if (inputCuota) inputCuota.required = true;
                    if (inputPlazo) inputPlazo.required = true;

                    // Cálculo equitativo proporcional por lote (Siempre >= 0)
                    let nuevoPrecio = Math.max(0, Math.round((precioPorLoteOriginal * conservados) * 100) / 100);
                    let nuevaCuota = Math.max(0, Math.round((cuotaPorLoteOriginal * conservados) * 100) / 100);
                    let nuevoPlazo = plazoOriginal;

                    if (inputPrecio) inputPrecio.value = nuevoPrecio.toFixed(2);
                    if (inputCuota) inputCuota.value = nuevaCuota.toFixed(2);
                    if (inputPlazo) inputPlazo.value = nuevoPlazo;

                    const cuotaIndividual = conservados > 0 ? Math.max(0, nuevaCuota / conservados) : 0;
                    const resumenEl = document.getElementById('resumen_proporcional_info');
                    if (resumenEl) {
                        resumenEl.innerHTML = 
                            `<strong><i class="fas fa-check-circle text-success me-1"></i> Conservando ${conservados} de ${totalLotes} lote(s):</strong><br>` +
                            `• Cuota individual por lote: <strong>$${cuotaIndividual.toFixed(2)}/mes</strong><br>` +
                            `• Nueva cuota mensual total: <strong class="text-success">$${nuevaCuota.toFixed(2)}/mes</strong><br>` +
                            `• Nuevo valor total del contrato: <strong>$${nuevoPrecio.toFixed(2)}</strong>`;
                    }
                } else {
                    if (containerParcial) containerParcial.style.display = 'none';
                    if (inputPrecio) inputPrecio.required = false;
                    if (inputCuota) inputCuota.required = false;
                    if (inputPlazo) inputPlazo.required = false;
                }

                // Actualizar visibilidad de opción 'Acreditar al lote que conserva'
                const tieneOtrosContratos = {{ (!empty($tieneOtrosContratos) && $tieneOtrosContratos) ? 'true' : 'false' }};
                const puedeAcreditar = (conservados > 0) || tieneOtrosContratos;
                const contenedorAcreditar = document.getElementById('contenedor_opcion_acreditar');
                const radioAcreditar = document.getElementById('dest_acreditar');
                const radioEfectivo = document.getElementById('dest_efectivo');

                if (contenedorAcreditar) {
                    if (puedeAcreditar) {
                        contenedorAcreditar.style.display = 'block';
                    } else {
                        contenedorAcreditar.style.setProperty('display', 'none', 'important');
                        if (radioAcreditar && radioAcreditar.checked) {
                            if (radioEfectivo) radioEfectivo.checked = true;
                        }
                    }
                }
            }

            checkboxes.forEach(chk => {
                chk.addEventListener('change', checkRescissionType);
            });

            // Autocalcular plazo garantizando valores >= 0
            function calcularPlazo() {
                if (!inputPrecio || !inputCuota || !inputPlazo) return;
                let precio = Math.max(0, parseFloat(inputPrecio.value) || 0);
                let cuota = Math.max(0, parseFloat(inputCuota.value) || 0);
                if (precio > 0 && cuota > 0) {
                    inputPlazo.value = Math.max(1, Math.ceil(precio / cuota));
                }
            }

            if (inputPrecio) inputPrecio.addEventListener('input', calcularPlazo);
            if (inputCuota) inputCuota.addEventListener('input', calcularPlazo);

            // Bloquear estrictamente negativos en inputs de rescisión
            [inputPrecio, inputCuota, inputPlazo].forEach(inp => {
                if (inp) {
                    inp.addEventListener('keydown', function(e) {
                        if (e.key === '-' || e.key === '+' || e.key === 'e' || e.key === 'E') {
                            e.preventDefault();
                        }
                    });
                    inp.addEventListener('input', function() {
                        if (parseFloat(this.value) < 0) {
                            this.value = 0;
                        }
                    });
                }
            });

            // Mostrar/Ocultar selector de contrato destino según destino de abonos seleccionado
            const radiosDestino = document.querySelectorAll('.destino-abonos-radio');
            const selectorContrato = document.getElementById('selector_contrato_destino');
            if (selectorContrato) {
                radiosDestino.forEach(radio => {
                    radio.addEventListener('change', function() {
                        let checkedCount = document.querySelectorAll('.lote-rescindir-checkbox:checked').length;
                        let esTotal = (checkedCount === totalLotes);
                        if (this.value === 'acreditar_otro_lote' && esTotal) {
                            selectorContrato.style.display = 'block';
                        } else {
                            selectorContrato.style.display = 'none';
                        }
                    });
                });
            }
            @endif

            // Toggle Acordeón Historial de Abonos (Abre y Cierra)
            $('#headerHistorialAbonos').on('click', function(e) {
                if ($(e.target).closest('button, a, input').length) return;
                
                $('#bodyHistorialAbonos').slideToggle(200, function() {
                    if ($(this).is(':visible')) {
                        $('#chevronAbonos').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $('#chevronAbonos').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                });
            });

            // Toggle Acordeón Historial de Modificaciones y Cesiones (Abre y Cierra)
            $('#headerHistorialModificaciones').on('click', function(e) {
                if ($(e.target).closest('button, a, input').length) return;
                
                $('#bodyHistorialModificaciones').slideToggle(200, function() {
                    if ($(this).is(':visible')) {
                        $('#chevronModificaciones').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $('#chevronModificaciones').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                });
            });

            // Toggle Acordeón Historial de Rescisiones y Desistimientos (Abre y Cierra)
            $('#headerHistorialRescisiones').on('click', function(e) {
                if ($(e.target).closest('button, a, input').length) return;
                
                $('#bodyHistorialRescisiones').slideToggle(200, function() {
                    if ($(this).is(':visible')) {
                        $('#chevronRescisiones').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $('#chevronRescisiones').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                });
            });

            // Auto-abrir recibos para imprimir si vienen de un registro reciente y está activo en parámetros
            @if(session('imprimir_abonos') && setting('auto_abrir_recibo', true))
                setTimeout(function() {
                    $('.btn-imprimir-auto').each(function() {
                        var url = $(this).attr('href');
                        if (url) {
                            window.open(url, '_blank');
                        }
                    });
                }, 400);
            @endif
        });
    </script>
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection