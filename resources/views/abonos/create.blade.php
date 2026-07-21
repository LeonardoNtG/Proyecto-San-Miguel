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
                    <span class="badge bg-white me-2">{{ $detalle['bloque'] }}-{{ $detalle['lote'] }} ({{ number_format($detalle['area'], 2) }} m²)</span>
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
                                            
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-abono" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-id="{{ $abono->id_abono }}" title="Borrar Abono">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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
                    <form action="{{ route('abono.store', $cliente->id_cliente) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto del Abono ($)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="monto" name="monto_abonado" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha de Pago</label>
                            <input type="date" class="form-control" id="fecha" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_pago" class="form-label">Tipo de Pago</label>
                            <select class="form-select" id="tipo_pago" name="tipo_pago" required>
                                <option value="Mensualidad">Mensualidad</option>
                                <option value="Extraordinario">Extraordinario</option>
                                <option value="Prima/Inicial" disabled>Prima/Inicial (Ya registrada)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="referencia" class="form-label">Referencia/Concepto (Opcional)</label>
                            <input type="text" class="form-control" id="referencia" name="referencia">
                        </div>
                        
                        <hr>
                        <div class="mb-4 text-center p-3 border rounded">
                            <label for="recibo_imagen" class="form-label d-block">Imagen del Recibo (Opcional)</label>
                        
                            <input type="file" class="form-control" id="ruta_recibo" name="ruta_recibo" accept="image/*">
                            <small class="text-muted mt-2 d-block">JPG o PNG del comprobante de pago.</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-plus-circle"></i> Guardar Abono 
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
            </div>
            <div class="modal-body">
                <p>¿Estás ABSOLUTAMENTE SEGURO de que deseas eliminar este registro?</p>
                <p class="text-danger">Esta acción es irreversible y eliminará los datos del abono.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                
                <form id="form-eliminar-abono" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script>
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
@endpush