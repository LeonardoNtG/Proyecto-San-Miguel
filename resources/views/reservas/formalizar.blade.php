@extends('template')

@section('titulo', 'Formalizar Reserva')

@section('contenido')
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
                    <strong>Lotes:</strong> 
                    @foreach($reserva->lotes as $lote)
                        <span class="badge bg-secondary text-white">L-{{ $lote->numero_lote }}</span>
                    @endforeach
                </div>
                <div class="col-md-4 mb-3">
                    <strong>Monto de Reserva Abonado:</strong><br>
                    <h4 class="text-success font-weight-bold">${{ number_format($reserva->monto_reserva, 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: DETALLES FINANCIEROS DE LA VENTA --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-money-check-alt"></i> Datos Financieros del Contrato</h6>
        </div>
        <div class="card-body bg-white">
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="precio_final" class="form-label font-weight-bold text-secondary">Precio Final del Contrato</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" class="form-control" id="precio_final" name="precio_final" value="{{ old('precio_final') }}" required>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="primer_abono" class="form-label font-weight-bold text-secondary">Prima Total (Incluye reserva)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold text-primary" id="primer_abono" name="primer_abono" value="{{ old('primer_abono', $reserva->monto_reserva) }}" min="{{ $reserva->monto_reserva }}" required>
                    </div>
                    <small class="text-muted">No puede ser menor al monto ya reservado (${{ number_format($reserva->monto_reserva, 2) }})</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="fecha_ultimo_abono" class="form-label font-weight-bold text-secondary">Fecha de Pago de Prima</label>
                    <input type="date" class="form-control" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="plazo_meses" class="form-label font-weight-bold text-secondary">Plazo de Financiamiento</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="plazo_meses" name="plazo_meses" value="{{ old('plazo_meses') }}" placeholder="Ej: 60" required>
                        <span class="input-group-text">Meses</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cuota_mensual" class="form-label font-weight-bold text-secondary">Cuota Mensual Estimada</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" class="form-control" id="cuota_mensual" name="cuota_mensual" value="{{ old('cuota_mensual') }}" required readonly>
                    </div>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="button" id="btnCalcular" class="btn btn-info w-100"><i class="fas fa-calculator"></i> Calcular Cuota</button>
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
    $('#btnCalcular').click(function() {
        let precio = parseFloat($('#precio_final').val()) || 0;
        let prima = parseFloat($('#primer_abono').val()) || 0;
        let plazo = parseInt($('#plazo_meses').val()) || 0;

        if (plazo > 1 && precio > prima) {
            let saldo = precio - prima;
            let cuota = saldo / (plazo - 1);
            $('#cuota_mensual').val(cuota.toFixed(2));
        } else {
            alert('Asegúrese de llenar Precio, Prima y Plazo correctamente. El plazo debe ser mayor a 1.');
        }
    });

    $('#precio_final, #primer_abono, #plazo_meses').on('input', function() {
        $('#cuota_mensual').val('');
    });
});
</script>
<script src="{{ asset('js/jqueryEM.js') }}"></script>
<script src="{{ asset('js/sbAdmin2M.js') }}"></script>
@endsection
