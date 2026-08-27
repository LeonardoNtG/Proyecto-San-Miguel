@extends('template')

@section('titulo', 'Formalizar Reserva')

@section('contenido')
<style>
    .financial-card {
        border-radius: 10px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .financial-card:hover {
        transform: translateY(-2px);
    }
    .bg-area { background: linear-gradient(45deg, #36b9cc, #2c9faf); }
    .bg-precio { background: linear-gradient(45deg, #1cc88a, #13855c); }
    
    /* Ocultar flechas en inputs number */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

@php
    $extensionTotal = $reserva->lotes->sum('area_metros');
    $precioBaseTotal = $reserva->lotes->sum('precio_base');
@endphp

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-signature text-success"></i> Formalizar Reserva #{{ $reserva->id_reserva }}</h1>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('reservas.procesarFormalizacion', $reserva->id_reserva) }}" method="POST">
    @csrf

    {{-- SECCIÓN 1: RESUMEN DE LA RESERVA (SOLO LECTURA) --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-info-circle"></i> Resumen de la Reserva</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <strong>Cliente:</strong><br>
                    {{ $reserva->cliente->nombres_apellidos }} <br>
                    <small class="text-muted">{{ $reserva->cliente->identificacion }}</small>
                </div>
                <div class="col-md-4 mb-3">
                    <strong>Proyecto:</strong><br>
                    {{ $reserva->lotificacion->nombre }} <br>
                </div>
                <div class="col-md-4 mb-3">
                    <strong>Monto de Reserva Abonado:</strong><br>
                    <h4 class="text-success font-weight-bold">${{ number_format($reserva->monto_reserva, 2) }}</h4>
                </div>
            </div>

            <hr>
            
            <h6 class="font-weight-bold text-secondary mb-3"><i class="fas fa-layer-group"></i> Lotes Reservados (Detalles)</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm bg-white mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Bloque</th>
                            <th>Nº Lote</th>
                            <th>Extensión (vrs²)</th>
                            <th>Precio Base ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reserva->lotes as $lote)
                        <tr>
                            <td class="align-middle">Bloque {{ $lote->bloque ? $lote->bloque->nombre : 'N/D' }}</td>
                            <td class="align-middle"><span class="badge bg-secondary text-white" style="font-size: 0.9rem;">Lote {{ $lote->numero_lote }}</span></td>
                            <td class="align-middle">{{ number_format($lote->area_metros, 2) }} vrs²</td>
                            <td class="align-middle font-weight-bold text-success">${{ number_format($lote->precio_base, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: DETALLES FINANCIEROS DE LA VENTA --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-money-check-alt"></i> Datos Financieros del Contrato</h6>
        </div>
        <div class="card-body bg-light">
            <!-- Tarjetas de Totales -->
            <div class="row g-3 mb-4">
                <!-- Extensión -->
                <div class="col-md-6">
                    <div class="financial-card bg-area d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Extensión TOTAL</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($extensionTotal, 2) }} <small class="fs-6">vrs²</small></h3>
                        </div>
                        <i class="fas fa-ruler-combined fa-3x opacity-50"></i>
                    </div>
                </div>
                <!-- Precio -->
                <div class="col-md-6">
                    <div class="financial-card bg-precio d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Precio Venta (Total)</h6>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 fw-bold me-2">$</h3>
                                <input type="number" step="0.01" min="0" class="form-control bg-transparent text-white border-0 shadow-none fw-bold p-0" style="font-size: 1.4rem;" id="precio_final" name="precio_final" placeholder="0.00" value="{{ old('precio_final', $precioBaseTotal) }}" required>
                            </div>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>

            <hr class="mb-4">

            <!-- Inputs de Prima y Fecha -->
            <div class="row g-3 mb-2">
                <div class="col-md-6 mb-3">
                    <label for="primer_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-money-bill-wave text-success"></i> Prima Total (Incluye reserva)</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-success text-white border-success">$</span>
                        <input type="number" step="0.01" min="0" class="form-control border-success" id="primer_abono" name="primer_abono" value="{{ old('primer_abono', $reserva->monto_reserva) }}" min="{{ $reserva->monto_reserva }}" required>
                    </div>
                    <small class="text-muted">No puede ser menor al monto ya reservado (${{ number_format($reserva->monto_reserva, 2) }})</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_ultimo_abono" class="form-label font-weight-bold text-secondary"><i class="fas fa-calendar-alt text-primary"></i> Fecha de Pago de Prima</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar text-muted"></i></span>
                        <input type="date" class="form-control border-start-0 ps-0" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ old('fecha_ultimo_abono', date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="plazo_meses" class="form-label font-weight-bold text-secondary">Plazo de Financiamiento</label>
                    <div class="input-group">
                        <input type="number" min="1" class="form-control" id="plazo_meses" name="plazo_meses" value="{{ old('plazo_meses') }}" placeholder="Ej: 60" required>
                        <span class="input-group-text">Meses</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cuota_mensual" class="form-label font-weight-bold text-secondary">Cuota Mensual Sugerida</label>
                    <div class="input-group">
                        <span class="input-group-text text-danger font-weight-bold">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold" style="font-size: 1.1rem; color: #e74a3b;" id="cuota_mensual" name="cuota_mensual" placeholder="0.00" value="{{ old('cuota_mensual') }}" required>
                    </div>
                    <small class="text-muted">Se recalcula automáticamente.</small>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-center">
                    <button type="button" id="btnCalcular" class="btn btn-info w-100 py-2 font-weight-bold shadow-sm"><i class="fas fa-calculator"></i> Recalcular Cuota</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <a href="{{ route('reservas.index') }}" class="btn btn-secondary btn-lg mr-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="submit" class="btn btn-success btn-lg shadow-sm px-5"><i class="fas fa-check-double"></i> Confirmar y Generar Contrato</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function calculateQuota() {
        let precio = parseFloat($('#precio_final').val()) || 0;
        let prima = parseFloat($('#primer_abono').val()) || 0;
        let plazo = parseInt($('#plazo_meses').val()) || 0;

        if (plazo > 1 && precio >= prima) {
            let saldo = precio - prima;
            let cuota = saldo / (plazo - 1);
            $('#cuota_mensual').val(cuota.toFixed(2));
        } else {
            $('#cuota_mensual').val('');
        }
    }

    $('#btnCalcular').click(function() {
        let precio = parseFloat($('#precio_final').val()) || 0;
        let prima = parseFloat($('#primer_abono').val()) || 0;
        let plazo = parseInt($('#plazo_meses').val()) || 0;

        if (plazo <= 1 || precio < prima) {
            alert('Asegúrese de llenar Precio, Prima y Plazo correctamente. El plazo debe ser mayor a 1.');
        } else {
            calculateQuota();
        }
    });

    $('#precio_final, #primer_abono, #plazo_meses').on('input', function() {
        calculateQuota();
    });

    // Evitar que el scroll del mouse cambie el valor de los inputs tipo número
    $('input[type=number]').on('wheel', function(e) {
        e.preventDefault();
    });

    // Calcular cuota inicialmente si hay datos
    calculateQuota();
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
