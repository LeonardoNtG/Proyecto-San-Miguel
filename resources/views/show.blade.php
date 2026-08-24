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
                <i class="fas fa-edit"></i> Editar Cliente/Venta
             </a>
    
            <button type="button" class="btn btn-danger" 
                    data-bs-toggle="modal" 
                    data-bs-target="#deleteModal">
                <i class="fas fa-trash"></i> Eliminar Cliente
            </button>
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
                                <p><strong>Proyecto:</strong> {{ $venta->proyecto ?? 'N/A' }}</p>
                                <p><strong>Precio Final:</strong> ${{ number_format($venta->precio_final, 2) }}</p>
                                <p><strong>Plazo (Meses):</strong> {{ $venta->plazo_meses }}</p>
                                <p><strong>Cuota Mensual:</strong> ${{ number_format($venta->cuota_mensual, 2) }}</p>
                                <p><strong>Extensión Total:</strong> {{ $venta->extension_lote }} m²</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Lotes Vendidos ({{ $venta->total_lotes_vendidos }}):</h5>
                                <ul>
                                    @foreach ($venta->lotes as $lote)
                                        <li>Bloque: *{{ $lote->bloque->nombre }}*, Lote: *{{ $lote->numero_lote }}* ({{ number_format($lote->area_metros, 2) }} m²)</li>
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
    
    {{--HISTORIAL DE ABONOS --}}
    @if($venta && $venta->abonos->count())
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">Historial de Pagos (Abonos)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Deuda Pendiente:</strong> ${{ number_format($venta->precio_final - $venta->total_abonado, 2) }} 
                    (Abonado: ${{ number_format($venta->total_abonado, 2) }})
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Monto Abonado</th>
                                <th>Tipo de Pago</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venta->abonos as $abono)
                                <tr>
                                    <td>{{ $abono->fecha_pago }}</td>
                                    <td>${{ number_format($abono->monto_abonado, 2) }}</td>
                                    <td>{{ $abono->tipo_pago }}</td>
                                    <td>{{ $abono->referencia }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
   

@endsection

@section('scripts')
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
    
@endsection