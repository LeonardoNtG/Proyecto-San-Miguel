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
                        <label for="nombre_completo" class="form-label">Nombre Completo / Representante <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="nombre_completo" name="nombres_apellidos" 
                               value="{{ old('nombres_apellidos', $cliente->nombres_apellidos) }}" style="text-transform: uppercase;" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cedula" class="form-label">Cédula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="cedula" name="identificacion" 
                               value="{{ old('identificacion', $cliente->identificacion) }}" maxlength="16" placeholder="000-000000-0000A" style="text-transform: uppercase;" required>
                        <small class="text-muted"><i class="fas fa-id-card text-primary"></i> Formato: XXX-XXXXXX-XXXXX</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="pv_num" class="form-label">N° Promesa Venta (PV) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="pv_num" name="pv_num" 
                               value="{{ old('pv_num', $cliente->pv_num) }}" style="text-transform: uppercase;" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="expediente_num" class="form-label">N° de Expediente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="expediente_num" name="expediente_num" 
                               value="{{ old('expediente_num', $cliente->expediente_num) }}" style="text-transform: uppercase;" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="{{ old('telefono', $cliente->telefono) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="estado_civil" class="form-label">Estado Civil</label>
                        <select class="form-control custom-select text-uppercase" id="estado_civil" name="estado_civil" style="text-transform: uppercase;" required>
                            <option value="">Seleccione...</option>
                            @php $ec = strtoupper(old('estado_civil', $cliente->estado_civil)); @endphp
                            @foreach (['SOLTERO' => 'Soltero(a)', 'CASADO' => 'Casado(a)', 'UNION_LIBRE' => 'Unión Libre', 'UNION_DE_HECHO' => 'Unión de Hecho', 'DIVORCIADO' => 'Divorciado(a)', 'VIUDO' => 'Viudo(a)'] as $opcion => $label)
                                <option value="{{ $opcion }}" {{ ($ec == $opcion || $ec == strtolower($opcion)) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="oficio" class="form-label">Oficio</label>
                        <input type="text" class="form-control text-uppercase" id="oficio" name="oficio"
                               value="{{ old('oficio', $cliente->oficio) }}" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control text-uppercase" id="direccion" name="direccion" rows="2" style="text-transform: uppercase;">{{ old('direccion', $cliente->direccion) }}</textarea>
                </div>
                
                <div class="mb-4 p-3 bg-light border rounded">
                    <label for="motivo_modificacion" class="form-label fw-bold text-dark">
                        <i class="fas fa-clipboard-list text-warning"></i> Motivo de la Modificación o Cesión: <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="motivo_modificacion" name="motivo_modificacion" rows="2" 
                              placeholder="Ejemplo: Cesión de derechos de lote a familiar / Corrección ortográfica en apellido / Actualización de número de cédula / Cambio de número de teléfono..." required>{{ old('motivo_modificacion') }}</textarea>
                    <small class="text-muted"><i class="fas fa-shield-alt text-primary"></i> Este motivo y los datos anteriores quedarán registrados en el historial de auditoría y expediente del cliente como respaldo legal.</small>
                </div>
                
                <hr class="my-4">
                
                <button type="submit" class="btn btn-warning btn-lg">
                    <i class="fas fa-save"></i> Actualizar Información
                </button>
                <a href="{{ route('registro.show', $cliente->id_cliente) }}" class="btn btn-secondary btn-lg">Cancelar</a>
            </form>
            
        </div>
    </div>

    <script>
    function formatearCedula(input) {
        let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (val.length > 14) val = val.substring(0, 14);

        let formatted = '';
        if (val.length > 0) {
            formatted += val.substring(0, Math.min(3, val.length));
        }
        if (val.length > 3) {
            formatted += '-' + val.substring(3, Math.min(9, val.length));
        }
        if (val.length > 9) {
            formatted += '-' + val.substring(9, 14);
        }
        input.value = formatted;
    }

    document.addEventListener("DOMContentLoaded", function() {
        const cedulaInput = document.getElementById('cedula');
        if (cedulaInput) {
            cedulaInput.addEventListener('input', function() {
                formatearCedula(this);
            });
            cedulaInput.addEventListener('blur', function() {
                formatearCedula(this);
            });
        }

        document.querySelectorAll('.text-uppercase').forEach(function(el) {
            el.addEventListener('input', function() {
                if (this.id !== 'cedula') {
                    this.value = this.value.toUpperCase();
                }
            });
        });
    });
    </script>
@endsection