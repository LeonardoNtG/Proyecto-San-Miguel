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
                    <input type="text" name="search" class="form-control" placeholder="Buscar por N° Exp, Nombre, Cédula, Lote o Bloque..." value="{{ $search ?? '' }}">
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
                                $ventasActivas = $cliente->ventas->where('estado_contrato', '!=', 'Rescindido');
                                $primerVenta = $ventasActivas->first() ?? $cliente->ventas->first();
                                
                                $todosLotes = $ventasActivas->flatMap->lotes;
                                $cuotasPagadas = $ventasActivas->sum(function($v) {
                                    return $v->cuotas->where('estado', 'Pagada')->count();
                                });
                                $cuotasPendientes = $ventasActivas->sum(function($v) {
                                    return $v->cuotas->whereIn('estado', ['Pendiente', 'Mora'])->count();
                                });
                                $totalAbonado = $ventasActivas->sum(function($v) {
                                    return $v->abonos->sum('monto_abonado');
                                });
                                $saldoTotal = $ventasActivas->sum(function($v) {
                                    $saldo = $v->cuotas->where('estado', '!=', 'Pagada')->sum('saldo_restante');
                                    $mora = $v->cuotas->where('estado', '!=', 'Pagada')->sum('mora_pendiente');
                                    return $saldo + $mora;
                                });
                            @endphp
                            <tr>
                                <td>{{ $cliente->expediente_num }}</td>
                                <td>{{ $cliente->nombres_apellidos }}</td>
                                <td>
                                    @if($todosLotes->count() > 0)
                                        @foreach($todosLotes as $lote)
                                            <span class="badge badge-info">Bloque {{ $lote->bloque->nombre ?? 'N/A' }} - Lote {{ $lote->numero_lote ?? 'N/A' }}</span><br>
                                        @endforeach
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $primerVenta ? \Carbon\Carbon::parse($primerVenta->fecha_venta)->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td>
                                    <span class="badge badge-success">{{ $cuotasPagadas }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-warning text-dark">{{ $cuotasPendientes }}</span>
                                </td>
                                <td>
                                    ${{ number_format($totalAbonado, 2) }}
                                </td>
                                <td>
                                    <span class="text-danger fw-bold">${{ number_format($saldoTotal, 2) }}</span>
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
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
                // Si el usuario borra todo, buscamos de inmediato (recarga dinámica)
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
