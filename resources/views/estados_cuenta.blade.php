@extends('template')

@section('titulo', 'Estados de Cuenta de Clientes')

@section('contenido')
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="text-primary">Gestión de Estados de Cuenta</h2>
            <p class="text-muted mb-0">Copia y envía los enlaces seguros a los clientes para que vean su estado financiero.</p>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <form method="GET" action="{{ route('estados_cuenta') }}" class="form-inline m-0">
                <div class="input-group w-100">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por Nombre, Cédula o Expediente..." value="{{ $search }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Nº Exp.</th>
                            <th>Nombres y Apellidos</th>
                            <th>Lotes (Bloque-Lote)</th>
                            <th>Fecha Venta</th>
                            <th>Cuotas Pagadas</th>
                            <th>Cuotas Pendientes</th>
                            <th>Total Abonado</th>
                            <th>Saldo Pendiente</th>
                            <th class="text-center">Enlaces al Portal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            @php
                                $venta = $cliente->ventas->first();
                                $cuotasPagadas = $venta ? $venta->cuotas->where('estado', 'Pagada')->count() : 0;
                                $cuotasPendientes = $venta ? $venta->cuotas->whereIn('estado', ['Pendiente', 'Mora'])->count() : 0;
                            @endphp
                            <tr>
                                <td>{{ $cliente->expediente_num }}</td>
                                <td>{{ $cliente->nombres_apellidos }}</td>
                                <td>
                                    @if($venta && $venta->lotes->count() > 0)
                                        @foreach($venta->lotes as $lote)
                                            <span class="badge badge-info">Bloque {{ $lote->bloque->nombre ?? 'N/A' }} - Lote {{ $lote->numero_lote ?? 'N/A' }}</span><br>
                                        @endforeach
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td>
                                    <span class="badge badge-success">{{ $cuotasPagadas }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-warning text-dark">{{ $cuotasPendientes }}</span>
                                </td>
                                <td>
                                    ${{ number_format($venta ? $venta->abonos->sum('monto_abonado') : 0, 2) }}
                                </td>
                                <td>
                                    @php
                                        $saldo = $venta ? $venta->cuotas->where('estado', '!=', 'Pagada')->sum('saldo_restante') : 0;
                                        $mora = $venta ? $venta->cuotas->where('estado', '!=', 'Pagada')->sum('mora_pendiente') : 0;
                                    @endphp
                                    <span class="text-danger fw-bold">${{ number_format($saldo + $mora, 2) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($cliente->token_seguimiento)
                                        <button type="button" class="btn btn-sm btn-info text-white" onclick="navigator.clipboard.writeText('{{ route('portal.estado_cuenta', $cliente->token_seguimiento) }}'); alert('¡Enlace del portal copiado al portapapeles!');" title="Copiar Link">
                                            <i class="fas fa-copy"></i> Copiar Link
                                        </button>
                                        <a href="{{ route('portal.estado_cuenta', $cliente->token_seguimiento) }}" target="_blank" class="btn btn-sm btn-secondary" title="Abrir Portal">
                                            <i class="fas fa-external-link-alt"></i> Ver Portal
                                        </a>
                                    @else
                                        <span class="badge badge-warning">Sin Token</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No se encontraron clientes activos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $clientes->appends(['search' => $search])->links() }}
            </div>
        </div>
    </div>
@endsection
