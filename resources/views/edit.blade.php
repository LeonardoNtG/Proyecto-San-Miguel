@extends('template')

@section('titulo', 'Editar Cliente y Venta: ' . $cliente->expediente_num)

@section('contenido')
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Editar Información de Cliente y Venta de Lote</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('registro.update', $cliente->id_cliente) }}" method="POST">
                @csrf 
                @method('PUT')

                {{-- ================================================= --}}
                {{--  DATOS PERSONALES DEL CLIENTE --}}
                {{-- ================================================= --}}
                <h4 class="mb-3 text-info">Datos del Cliente / Representante</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre_completo" class="form-label">Nombre Completo / Representante</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombres_apellidos" 
                               value="{{ old('nombres_apellidos', $cliente->nombres_apellidos) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="identificacion" 
                               value="{{ old('identificacion', $cliente->identificacion) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="pv_num" class="form-label">N° Promesa Venta (PV)</label>
                        <input type="text" class="form-control" id="pv_num" name="pv_num" 
                               value="{{ old('pv_num', $cliente->pv_num) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="expediente_num" class="form-label">N° de Expediente</label>
                        <input type="text" class="form-control" id="expediente_num" name="expediente_num" 
                               value="{{ old('expediente_num', $cliente->expediente_num) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="{{ old('telefono', $cliente->telefono) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="estado_civil" class="form-label">Estado Civil</label>
                        <select class="form-control custom-select" id="estado_civil" name="estado_civil" required>
                            <option value="">Seleccione...</option>
                            @php $ec = old('estado_civil', $cliente->estado_civil); @endphp
                            @foreach (['soltero', 'casado', 'union_libre', 'divorciado', 'viudo'] as $opcion)
                                <option value="{{ $opcion }}" {{ $ec == $opcion ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $opcion)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="oficio" class="form-label">Oficio</label>
                        <input type="text" class="form-control" id="oficio" name="oficio"
                               value="{{ old('oficio', $cliente->oficio) }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                </div>
                
                <hr class="my-4">

                {{-- ================================================= --}}
                {{--  ESTADO DE LA VENTA --}}
                {{-- ================================================= --}}
                @if($venta)
                    <h4 class="mb-3 text-info">Estado y Parámetros de la Venta</h4>

                    <div class="alert alert-warning mb-4">
                        Solo el Estado del Contrato es editable desde aquí. Para cambiar lotes, precio o cuotas, es mejor rescindir y crear una nueva venta.
                    </div>
                    @php $esRescindido = ($venta->estado_contrato == 'Rescindido'); @endphp
                    <div class="mb-3">
                        <label for="estado_contrato" class="form-label fw-bold">Estado del Contrato:</label>
                        <select class="form-control custom-select {{ $esRescindido ? 'border-danger bg-light' : '' }}" 
                         id="estado_contrato" 
                        name="estado_contrato" 
                        {{ $esRescindido ? 'disabled' : '' }} required>
        
                     @foreach (['Vigente', 'Rescindido', 'Finalizado'] as $opcion)
                    <option value="{{ $opcion }}" {{ (old('estado_contrato', $venta->estado_contrato) == $opcion) ? 'selected' : '' }}>
                 {{ $opcion }}
                </option>
                 @endforeach
             </select>
    
         @if($esRescindido)
         <input type="hidden" name="estado_contrato" value="Rescindido">
             <div class="form-text text-danger">
             <i class="fas fa-exclamation-triangle"></i> Este contrato ha sido rescindido y no puede reactivarse.
             </div>
         @endif
        </div>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-bold text-secondary">Lotes Adquiridos en este Contrato</label>
                            <div class="p-3 border rounded bg-white">
                                @forelse($venta->lotes as $lote)
                                    <span class="badge bg-info text-dark p-2 mr-2 mb-2" style="font-size: 0.95rem;">
                                        <i class="fas fa-map-marker-alt"></i> Bloque {{ $lote->bloque ? $lote->bloque->nombre : 'N/D' }} | Lote {{ $lote->numero_lote }}
                                    </span>
                                @empty
                                    <span class="text-muted">No hay lotes asignados a esta venta.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="precio_final" class="form-label fw-bold text-secondary">Precio Final (USD)</label>
                            {{-- Solo lectura para referencia --}}
                            <input type="text" class="form-control bg-white" value="${{ number_format($venta->precio_final, 2) }}" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cuota_mensual" class="form-label fw-bold text-secondary">Cuota Mensual (USD)</label>
                            {{-- Solo lectura para referencia --}}
                            <input type="text" class="form-control bg-white" value="${{ number_format($venta->cuota_mensual, 2) }}" readonly>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger">No hay una venta activa asociada a este cliente para editar.</div>
                @endif
                

                <hr class="my-4">
                
                <button type="submit" class="btn btn-warning btn-lg">
                    <i class="fas fa-save"></i> Actualizar Información
                </button>
                <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="btn btn-secondary btn-lg">Cancelar</a>
            </form>
            
        </div>
    </div>
@section('scripts')

<script>
document.getElementById('estado_contrato').addEventListener('change', function() {
    if (this.value === 'Rescindido') {
        const confirmacion = confirm(
            "¡ADVERTENCIA CRÍTICA!\n\n" +
            "Si marca este contrato como 'Rescindido':\n" +
            "1. Los lotes asociados quedarán DISPONIBLES de inmediato.\n" +
            "2. Esta acción NO se puede deshacer desde esta pantalla.\n" +
            "3. Para reactivar al cliente, deberá crear un contrato nuevo.\n\n" +
            "¿Está absolutamente seguro de continuar?"
        );
        
        if (!confirmacion) {
            this.value = 'Vigente'; // Revertir si cancela
        }
    }
});
</script>
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
@endsection