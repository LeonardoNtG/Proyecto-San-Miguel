@extends('template') 

@section('titulo', 'Listado de Clientes') 

@section('contenido') 

<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Clientes Registrados (Ventas Activas)</h6>
            <a href="{{ route('registro.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Registrar Nuevo Cliente
            </a>
        </div>
        <div class="card-body">
            
            {{-- Search Bar --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('registro.index') }}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por N° Exp, Nombre o Cédula..." 
                               value="{{ $search ?? '' }}">
                        <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                        @if($search)
                            <a href="{{ route('registro.index') }}" class="btn btn-outline-danger">Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>N° Exp.</th>
                            <th>N° PV</th>
                            <th>Nombres y Apellidos</th>
                            <th>Estado de Venta</th>
                            <th>Total Abonado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr>
                                <td>{{ $cliente->expediente_num }}</td>
                                <td>{{ $cliente->pv_num }}</td>
                                <td>{{ $cliente->nombres_apellidos }}</td>
                                
                                {{-- ASUMIMOS UNA VENTA ACTIVA POR CLIENTE --}}
                                @php
                                    // Tomamos la primera venta 
                                    $ventaActiva = $cliente->ventas->first(); 
                                @endphp

                                @if($ventaActiva)
                                    <td>
                                        <span class="badge 
                                            @if($ventaActiva->estado_contrato == 'Vigente') bg-success text-white
                                             @elseif($ventaActiva->estado_contrato == 'Finalizado') bg-info text-white
                                             @else bg-danger text-white
                                            @endif">
                                            {{ $ventaActiva->estado_contrato }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($ventaActiva->total_abonado, 2) }}</td>
                                    <td>{{ $ventaActiva->created_at->format('d/M/Y') }}</td> 
                                @else
                                    <td colspan="2"><span class="badge bg-secondary text-white">Sin Venta Activa</span></td>
                                    <td>-</td>
                                @endif

                                <td>
                                {{-- Botón Abonar: Llevará al formulario de abonos --}}
                                    @php 
                            $venta = $cliente->ventas->first(); 
                            $esRescindido = ($venta && $venta->estado_contrato === 'Rescindido');
                       @endphp

                            @if($esRescindido)
                             {{-- Alerta visual cuando el contrato está muerto --}}
                                <div class="alert alert-danger d-flex align-items-center mt-3 shadow-sm" role="alert">
                                 <div>
                                     <div class="small">Cuenta Cerrada / Contrato Rescindido</div>
                                </div>
                            </div>
                         @else
                                    <a href="{{ route('abono.create', ['cliente' => $cliente->id_cliente]) }}" class="btn btn-sm btn-success" title="Registrar Abono">
                                        <i class="fas fa-hand-holding-usd"></i> Abonar
                                    </a>
                                    {{-- Botón Detalles: Llevará a la vista de información completa --}}
                                    <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="btn btn-sm btn-info" title="Ver Detalles">
                                        <i class="fas fa-info-circle"></i> Detalles
                                    </a>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron clientes registrados o que coincidan con la búsqueda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="mt-3">
                {{ $clientes->appends(['search' => $search])->links() }}
            </div>
        </div>
 </div>

@endsection 

@section('scripts')
    <script>
            <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
    
@endsection