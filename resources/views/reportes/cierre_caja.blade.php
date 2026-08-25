@extends('template')

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0"><i class="fas fa-calculator text-primary"></i> Cierre de Caja Diaria</h2>
        
        <form method="GET" action="{{ route('reportes.cierre_caja') }}" class="d-flex align-items-center">
            <label for="fecha" class="me-2 fw-bold text-secondary">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control me-2" onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="letter-spacing: 1px; font-size: 0.8rem;">Total Ingresos General</h6>
                    <h3 class="mb-0 fw-bold">${{ number_format($totalGeneral, 2) }}</h3>
                </div>
            </div>
        </div>
        
        @foreach($totales as $metodo => $total)
            @if($total > 0 || in_array($metodo, ['Efectivo', 'Transferencia Bancaria']))
            <div class="col-md-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-1 text-secondary" style="letter-spacing: 1px; font-size: 0.75rem;">{{ $metodo }}</h6>
                        <h4 class="mb-0 text-dark fw-bold">${{ number_format($total, 2) }}</h4>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <!-- Tabla Detallada -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Detalle de Ingresos del {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Lote(s)</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Detalle / Cuenta</th>
                            <th>Monto</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abonos as $abono)
                        <tr>
                            <td>
                                @if($abono->venta && $abono->venta->cliente)
                                    <a href="{{ route('clientes.show', $abono->venta->cliente->id_cliente) }}" class="text-decoration-none fw-bold">
                                        {{ $abono->venta->cliente->nombres_apellidos }}
                                    </a>
                                @else
                                    <span class="text-muted">Cliente Desconocido</span>
                                @endif
                            </td>
                            <td>
                                @if($abono->venta)
                                    @foreach($abono->venta->lotes as $lote)
                                        <span class="badge bg-secondary">{{ $lote->bloque->nombre_bloque ?? 'Bloque' }} - Lote {{ $lote->numero_lote }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $abono->tipo_pago }}</td>
                            <td>
                                @if($abono->metodo_pago == 'Efectivo')
                                    <span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> Efectivo</span>
                                @elseif($abono->metodo_pago == 'Transferencia Bancaria')
                                    <span class="badge bg-info text-dark"><i class="fas fa-exchange-alt"></i> Transferencia</span>
                                @else
                                    <span class="badge bg-secondary">{{ $abono->metodo_pago }}</span>
                                @endif
                            </td>
                            <td>
                                @if($abono->referencia)
                                    Ref: {{ $abono->referencia }}<br>
                                @endif
                                @if($abono->cuenta_destino)
                                    <small class="text-muted"><i class="fas fa-university"></i> {{ $abono->cuenta_destino }}</small>
                                @endif
                            </td>
                            <td class="text-success fw-bold">+${{ number_format($abono->monto_abonado, 2) }}</td>
                            <td>
                                <a href="{{ route('abonos.imprimir', $abono->id_abono) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Recibo">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                <h5>No hay ingresos registrados para esta fecha</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
