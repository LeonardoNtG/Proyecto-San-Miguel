@extends('template') 

@section('titulo', 'Listado de Clientes') 

@section('contenido') 

<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Clientes Registrados 
                ({{ request('filtro') === 'rescindidos' ? 'Ventas Rescindidas' : 'Ventas Activas' }})
            </h6>
            <div>
                <div class="btn-group mr-2" role="group">
                    <a href="{{ route('registro.index', ['filtro' => 'activos', 'search' => request('search')]) }}" class="btn btn-sm {{ request('filtro', 'activos') === 'activos' ? 'btn-primary' : 'btn-outline-primary' }}">Activos</a>
                    <a href="{{ route('registro.index', ['filtro' => 'rescindidos', 'search' => request('search')]) }}" class="btn btn-sm {{ request('filtro') === 'rescindidos' ? 'btn-danger' : 'btn-outline-danger' }}">Rescindidos</a>
                </div>
                <a href="{{ route('registro.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Registrar Nuevo Cliente
                </a>
            </div>
        </div>
        <div class="card-body">
            
            {{-- Search Bar --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('registro.index') }}">
                    <input type="hidden" name="filtro" value="{{ request('filtro', 'activos') }}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por N° Exp, Nombre, Cédula, Lote o Bloque..." 
                               value="{{ $search ?? '' }}">
                        <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                        @if($search)
                            <a href="{{ route('registro.index', ['filtro' => request('filtro', 'activos')]) }}" class="btn btn-outline-danger">Limpiar</a>
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
                            <th>Proyecto</th>
                            <th>Lotes (Bloque-Lote)</th>
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
                                                              @php
                                    $ventasActivas = $cliente->ventas->where('estado_contrato', '!=', 'Rescindido');
                                    $ventaPrincipal = $ventasActivas->first() ?? $cliente->ventas->first();
                                    $totalAbonadoCliente = $cliente->ventas->sum('total_abonado');
                                    
                                    // Obtener todos los lotes de todas las ventas del cliente (filtrando por activas si las hay)
                                    $ventasParaLotes = $ventasActivas->count() > 0 ? $ventasActivas : $cliente->ventas;
                                    $todosLotes = $ventasParaLotes->flatMap(function($v) {
                                        return $v->lotes;
                                    });

                                    // Proyectos involucrados
                                    $proyectosNombres = $ventasParaLotes->map(function($v) {
                                        return $v->lotificacion->nombre ?? null;
                                    })->filter()->unique()->implode(', ');
                                @endphp

                                <td>{{ $proyectosNombres ?: ($ventaPrincipal->lotificacion->nombre ?? 'N/A') }}</td>

                                <td>
                                    @if($todosLotes->count() > 0)
                                        @foreach($todosLotes as $lote)
                                            <span class="badge bg-info text-white mb-1">Bloque {{ $lote->bloque->nombre ?? 'N/A' }} - Lote {{ $lote->numero_lote ?? 'N/A' }}</span><br>
                                        @endforeach
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>

                                @if($ventaPrincipal)
                                    <td>
                                        <span class="badge 
                                            @if($ventasActivas->count() > 0) bg-success text-white
                                             @elseif($cliente->ventas->where('estado_contrato', 'Finalizado')->count() > 0) bg-info text-white
                                             @else bg-danger text-white
                                            @endif">
                                            @if($ventasActivas->count() > 1)
                                                {{ $ventasActivas->count() }} Contratos Vigentes
                                            @else
                                                {{ $ventaPrincipal->estado_contrato }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>${{ number_format($totalAbonadoCliente, 2) }}</td>
                                    <td>{{ $ventaPrincipal->created_at->translatedFormat('d/M/Y') }}</td> 
                                @else
                                    <td colspan="2"><span class="badge bg-secondary text-white">Sin Venta Activa</span></td>
                                    <td>-</td>
                                @endif

                                <td>
                                    {{-- Botón Abonar: Llevará al formulario de abonos --}}
                                    @php 
                                        $esRescindido = ($ventasActivas->count() === 0 && $cliente->ventas->count() > 0 && $cliente->ventas->every(fn($v) => $v->estado_contrato === 'Rescindido'));
                                    @endphp

                                    @if($esRescindido)
                                        {{-- Alerta visual cuando el contrato está rescindido --}}
                                        <div class="alert alert-danger d-flex align-items-center mb-0 p-2 shadow-sm" role="alert">
                                            <div class="small fw-bold">Cuenta Cerrada / Rescindido</div>
                                        </div>
                                    @else
                                        <a href="{{ route('abono.create', ['cliente' => $cliente->id_cliente]) }}" class="btn btn-sm btn-success" title="Registrar Abono">
                                            <i class="fas fa-hand-holding-usd"></i> Abonar
                                        </a>
                                        <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="btn btn-sm btn-info" title="Ver Detalles">
                                            <i class="fas fa-info-circle"></i> Detalles
                                        </a>
                                        @if($cliente->token_seguimiento)
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="navigator.clipboard.writeText('{{ route('portal.estado_cuenta', $cliente->token_seguimiento) }}'); alert('¡Enlace del portal copiado!');" title="Copiar Enlace Estado de Cuenta">
                                            <i class="fas fa-link"></i> Portal
                                        </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
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
    <script src="{{ asset('js/jqueryEM.js') }}"></script>
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>
    <script src="{{ asset('js/chartM.js') }}"></script>
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (!searchInput) return;
            
            const form = searchInput.closest('form');
            
            // Mantener el cursor al final del texto si el input tiene valor tras recargar
            if (searchInput.value.length > 0) {
                searchInput.focus();
                let val = searchInput.value;
                searchInput.value = '';
                searchInput.value = val;
            }

            let typingTimer;
            const doneTypingInterval = 500; // 500ms

            searchInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                if (this.value === '') {
                    // Si el usuario borra todo, buscamos de inmediato
                    form.submit();
                } else {
                    // Si está escribiendo, esperamos a que pause para enviar
                    typingTimer = setTimeout(() => {
                        form.submit();
                    }, doneTypingInterval);
                }
            });
        });
    </script>
@endsection