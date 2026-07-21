@extends('template') {{-- Hereda la plantilla principal --}}

@section('titulo', 'Inicio') {{-- Define el contenido de la sección 'titulo' --}}

@section('contenido') {{-- Abre la sección principal 'contenido' --}}

    <h1>Reportes Generales</h1>

<hr>

<div class="card mb-4 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 text-center">
            <thead class="bg-light">
                <tr>
                    <th>Efectivo Anterior</th>
                    <th>Ingresado Hoy</th>
                    <th class="text-primary">Efectivo Total (Suma)</th>
                    <th class="text-danger">Salidas / Gastos</th>
                    <th class="bg-dark text-white">Saldo en Caja</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="align-middle">${{ number_format($saldoInicial, 2) }}</td>
                    <td class="align-middle text-success">+ ${{ number_format($ingresosHoy, 2) }}</td>
                    <td class="align-middle fw-bold">${{ number_format($saldoInicial + $ingresosHoy, 2) }}</td>
                    <td class="align-middle text-danger">- ${{ number_format($egresosHoy, 2) }}</td>
                    <td class="align-middle fw-bold fs-5">${{ number_format($saldoFinalCaja, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalSalida">
        <i class="fas fa-minus-circle"></i> Registrar Salida
    </button>
</div>

{{-- Formulario para ver las salidas  --}}
<div class="card">
    <div class="card-header bg-white">
        <strong>Detalle de Movimientos (Salidas)</strong>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Descripción / Motivo</th>
                    <th class="text-end">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listaSalidas as $salida)
                    <tr>
                        <td>{{ $salida->created_at->format('H:i A') }}</td>
                        <td>{{ $salida->descripcion }}</td>
                        <td class="text-end text-danger">- ${{ number_format($salida->monto, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No hay salidas registradas hoy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal para registras una salida  --}}
<div class="modal fade" id="modalSalida" tabindex="-1" aria-labelledby="modalSalidaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('reportes.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalSalidaLabel">Registrar Salida de Efectivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto a retirar</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo de la salida</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Ej: Pago de recibo de luz, compra de papelería..." required></textarea>
                    </div>
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="#" method="POST" class="mt-4">
                    @csrf
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <button type="submit" class="btn btn-dark w-100">
                    <i class="fas fa-lock"></i> Realizar Cierre de Caja
                </button>
            </form>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection 

@section('scripts')
    <script>
    <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- scripts para todas las paginas-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Paginas -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
@endsection