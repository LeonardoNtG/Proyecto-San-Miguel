@extends('template')

@section('titulo', 'Editar Lote ' . $lote->numero_lote)

@section('contenido')

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Revisa los siguientes datos:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- BANNER DE ADVERTENCIA PARA LOTES VENDIDOS O RESERVADOS --}}
@if($lote->estado === 'Vendido' || $lote->estado === 'Reservado')
    <div class="alert alert-warning border-warning shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-triangle fa-2x text-warning me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-1">
                    Atención: Este lote se encuentra <span class="badge {{ $lote->estado === 'Vendido' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $lote->estado }}</span>
                    @if($cliente)
                        a <strong>{{ $cliente->nombres_apellidos }}</strong> (Exp: {{ $cliente->expediente_num ?: 'N/A' }})
                    @endif
                </h5>
                <p class="mb-0 small text-dark">
                    Modificar las medidas o el precio base en este formulario <strong>solo actualizará el inventario físico</strong> del lote. 
                    <strong>No alterará el saldo, las cuotas mensuales ni los pagos ya registrados</strong> en el contrato financiero del cliente.
                </p>
                @if($cliente)
                    <div class="mt-2">
                        <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="btn btn-sm btn-outline-dark fw-bold">
                            <i class="fas fa-user-tag me-1"></i> Ver Expediente del Cliente
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

<h2 class="mb-4 text-primary">
    Editar Lote {{ $lote->numero_lote }}
    <small class="text-muted fs-6 ms-2">Bloque {{ $bloque->nombre }} &middot; {{ $bloque->proyecto ?: '—' }}</small>
</h2>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Datos del Lote</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('lotes.update', $lote) }}" method="POST" id="formEditarLote">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">N° de Lote</label>
                    <input type="text" name="numero_lote" class="form-control" maxlength="10" value="{{ old('numero_lote', $lote->numero_lote) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Área (vrs²)</label>
                    <input type="number" step="0.01" min="0.01" id="area_varas_edit" class="form-control" value="{{ number_format(old('area_metros', $lote->area_metros) / 0.705, 2, '.', '') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Área (m²)</label>
                    <input type="number" step="0.01" min="0.01" id="area_metros_edit" name="area_metros" class="form-control" value="{{ number_format(old('area_metros', $lote->area_metros), 2, '.', '') }}" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Precio por vr² (USD)</label>
                    <input type="number" step="0.01" min="0.01" id="precio_vara_edit" class="form-control" value="{{ old('area_metros', $lote->area_metros) > 0 ? number_format(old('precio_base', $lote->precio_base) / (old('area_metros', $lote->area_metros) / 0.705), 2, '.', '') : '' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Precio Total Base (USD)</label>
                    <input type="number" step="0.01" min="0.01" id="precio_base_edit" name="precio_base" class="form-control font-weight-bold text-success" value="{{ number_format(old('precio_base', $lote->precio_base), 2, '.', '') }}" required>
                    <small class="text-muted">Área (vrs²) &times; Precio por vr²</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold d-block">Estado del Lote</label>
                    <div class="pt-1">
                        @if($lote->estado === 'Disponible')
                            <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-check-circle me-1"></i> Disponible</span>
                        @elseif($lote->estado === 'Reservado')
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="fas fa-bookmark me-1"></i> Reservado</span>
                        @else
                            <span class="badge bg-danger fs-6 px-3 py-2"><i class="fas fa-handshake me-1"></i> Vendido</span>
                        @endif
                        <small class="text-muted d-block mt-1">El estado se gestiona automáticamente por ventas/reservas.</small>
                    </div>
                </div>
            </div>

            @if($lote->estado === 'Vendido' || $lote->estado === 'Reservado')
                {{-- Botón que abre el modal de confirmación --}}
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConfirmarModificacion">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            @else
                {{-- Botón de envío directo para lotes disponibles --}}
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            @endif
            <a href="{{ route('lotes.index', $bloque) }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

{{-- MODAL DE CONFIRMACIÓN PARA LOTES VENDIDOS O RESERVADOS --}}
@if($lote->estado === 'Vendido' || $lote->estado === 'Reservado')
<div class="modal fade" id="modalConfirmarModificacion" tabindex="-1" aria-labelledby="modalConfirmarModificacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalConfirmarModificacionLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirmar Modificación de Lote {{ $lote->estado }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark mb-2">
                    Estás a punto de modificar los datos del <strong>Lote {{ $lote->numero_lote }}</strong> que actualmente está <strong>{{ $lote->estado }}</strong>
                    @if($cliente)
                        por <strong>{{ $cliente->nombres_apellidos }}</strong>.
                    @endif
                </p>
                <div class="alert alert-info py-2 px-3 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> <strong>Aviso importante:</strong> Esta modificación actualizará la ficha física del lote. El saldo y las cuotas pactadas en el contrato del cliente <strong>no sufrirán ningún cambio</strong>.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning fw-bold text-dark" onclick="document.getElementById('formEditarLote').submit();">
                    <i class="fas fa-check-circle me-1"></i> Sí, Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const factorVara = 0.705;

    const inputM2 = document.getElementById('area_metros_edit');
    const inputVrs2 = document.getElementById('area_varas_edit');
    const inputPrecioVara = document.getElementById('precio_vara_edit');
    const inputPrecioBase = document.getElementById('precio_base_edit');

    // Función para calcular Precio Base = Área Vrs² * Precio por Vara
    function calcularPrecioBase() {
        const vrs2 = parseFloat(inputVrs2.value);
        const precioVara = parseFloat(inputPrecioVara.value);

        if (!isNaN(vrs2) && !isNaN(precioVara) && vrs2 > 0 && precioVara > 0) {
            const total = vrs2 * precioVara;
            inputPrecioBase.value = total.toFixed(2);
        }
    }

    // Función para calcular Precio por Vara = Precio Base / Área Vrs²
    function calcularPrecioVara() {
        const vrs2 = parseFloat(inputVrs2.value);
        const precioBase = parseFloat(inputPrecioBase.value);

        if (!isNaN(vrs2) && !isNaN(precioBase) && vrs2 > 0 && precioBase > 0) {
            const precioVara = precioBase / vrs2;
            inputPrecioVara.value = precioVara.toFixed(2);
        }
    }

    // 1. Cuando se escribe en Área (m²) -> vrs² = m² / 0.705
    if (inputM2) {
        inputM2.addEventListener('input', function() {
            const m2 = parseFloat(this.value);
            if (!isNaN(m2) && m2 > 0) {
                const vrs2 = m2 / factorVara;
                inputVrs2.value = vrs2.toFixed(2);
                calcularPrecioBase();
            } else {
                inputVrs2.value = '';
            }
        });
    }

    // 2. Cuando se escribe en Área (vrs²) -> m² = vrs² * 0.705
    if (inputVrs2) {
        inputVrs2.addEventListener('input', function() {
            const vrs2 = parseFloat(this.value);
            if (!isNaN(vrs2) && vrs2 > 0) {
                const m2 = vrs2 * factorVara;
                inputM2.value = m2.toFixed(2);
                calcularPrecioBase();
            } else {
                inputM2.value = '';
            }
        });
    }

    // 3. Cuando se escribe en Precio por vr²
    if (inputPrecioVara) {
        inputPrecioVara.addEventListener('input', function() {
            calcularPrecioBase();
        });
    }

    // 4. Cuando se escribe directamente en Precio Total Base
    if (inputPrecioBase) {
        inputPrecioBase.addEventListener('input', function() {
            calcularPrecioVara();
        });
    }
});
</script>
@endsection
