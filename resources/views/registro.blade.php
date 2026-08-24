@extends('template') {{-- 1. Hereda la plantilla principal --}}

@section('titulo', 'Registro de Clientes') {{-- 2. Define el contenido de la sección 'titulo' --}}

@section('contenido') {{-- 3. Abre la sección principal 'contenido' --}}

@if (session('error'))
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
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

    <h1>Ingrese la Informacion del Cliente</h1>

<div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Registro de Cliente y Venta de Lote</h6>
        </div>
        <div class="card-body">
            
            <form action="{{ route('registro.store') }}" method="POST">
                @csrf

                {{-- ================================================= --}}
                {{-- SECCIÓN 1: DATOS PERSONALES DEL CLIENTE --}}
                {{-- ================================================= --}}
                <h4 class="mb-3 text-info">Datos del Cliente / Representante</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre_completo" class="form-label">Nombre Completo / Representante</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombres_apellidos" value="{{ old('nombres_apellidos') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="identificacion" value="{{ old('identificacion') }}" required>
                    </div>

                    <div class="col-md-3 mb-3">
                      <label for="pv_num" class="form-label">N° Promesa Venta (PV)</label>
                        <input type="text" class="form-control" id="pv_num" name="pv_num" value="{{ old('pv_num') }}" placeholder="Ej: PV-001" required>
                    </div>
                 </div>
                    <div class="col-md-3 mb-3">
                        <label for="expediente_num" class="form-label">N° de Expediente</label>
                        <input type="text" class="form-control" id="expediente_num" name="expediente_num" value="{{ old('expediente_num') }}" placeholder="Ej: EXP-005" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" value="{{ old('telefono') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="estado_civil" class="form-label">Estado Civil</label>
                        <select class="form-select" id="estado_civil" name="estado_civil" required>
                            <option value="">Seleccione...</option>
                            <option value="soltero" @selected(old('estado_civil') == 'soltero')>Soltero(a)</option>
                            <option value="casado" @selected(old('estado_civil') == 'casado')>Casado(a)</option>
                            <option value="union_libre" @selected(old('estado_civil') == 'union_libre')>Unión Libre</option>
                            <option value="divorciado" @selected(old('estado_civil') == 'divorciado')>Divorciado(a)</option>
                            <option value="viudo" @selected(old('estado_civil') == 'viudo')>Viudo(a)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="oficio" class="form-label">Oficio</label>
                        <input type="text" class="form-control" id="oficio" name="oficio" value="{{ old('oficio') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2">{{ old('direccion') }}</textarea>
                </div>
                
                <hr class="my-4">

                {{-- ================================================= --}}
                {{-- SECCIÓN 2: DATOS DE LA VENTA / LOTE --}}
                {{-- ================================================= --}}
                <h5 class="mb-3 text-info">Detalles de la Promesa de Venta</h5>
                
                <div class="row">
                    {{-- Proyecto (Select) --}}
                    <div class="col-md-3 mb-3">
                        <label for="proyecto_select" class="form-label">Proyecto</label>
                        <select class="form-select" id="proyecto_select" required>
                            <option value="">Seleccione Proyecto</option>
                            @isset($proyectos)
                                @foreach ($proyectos as $proyecto)
                                    <option value="{{ $proyecto }}" @selected(old('proyecto_seleccionado') == $proyecto)>{{ $proyecto }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    {{-- Bloque (Select dinámico, depende del Proyecto) --}}
                    <div class="col-md-3 mb-3">
                        <label for="bloque" class="form-label">Bloque</label>
                        <select class="form-select" id="bloque_select" name="bloque_id" required disabled>
                            <option value="">Seleccione un Proyecto primero</option>
                        </select>
                    </div>

         <div class="col-md-6 mb-3">
            <label for="lotes_seleccionados" class="form-label">Lotes Seleccionados (Máx. 20)</label>
             <select class="form-select" id="lote_select" name="lotes_ids[]" multiple required disabled size="6 ">
                 <option value="">Seleccione un Bloque primero</option>
            </select>
        </div>
        </div>

        <div class="row">
          <div class="col-md-3 mb-3">
                <label for="extension" class="form-label">Extensión TOTAL (m² o v²)</label>
               <input type="text" class="form-control" id="extension_lote" name="extension" placeholder="Se calcula automáticamente" readonly required>
               <input type="hidden" id="extension_lote_value" name="extension_value"> {{-- Campo oculto para el valor numérico --}}
         </div>
         <div class="col-md-4 mb-3">
               <label for="monto_lote" class="form-label">Monto TOTAL de Lotes (USD)</label>
               <input type="number" step="0.01" class="form-control" id="monto_lote" name="precio_final" placeholder="Se sugiere según los lotes elegidos" value="{{ old('precio_final') }}" required>
         </div>
         <div class="col-md-4 mb-3">
              <label for="plazo_cuotas" class="form-label">Plazo Total (Meses)</label>
              <input type="number" class="form-control" id="plazo_cuotas" name="plazo_meses" value="{{ old('plazo_meses') }}" required>
         </div>
         <div class="col-md-4 mb-3">
             <label for="cuotas" class="form-label">Valor de Cuota Mensual (USD)</label>
               <input type="number" step="0.01" class="form-control" id="cuotas" name="cuota_mensual" placeholder="Se calcula según monto, prima y plazo" value="{{ old('cuota_mensual') }}" required>
          </div>
                    <div class="col-md-3 mb-3">
                        <label for="primer_abono" class="form-label">Primer Abono/Prima (USD)</label>
                        <input type="number" step="0.01" class="form-control" id="primer_abono" name="primer_abono" value="{{ old('primer_abono') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="fecha_ultimo_abono" class="form-label">Fecha de Abonos</label>
                        <input type="date" class="form-control" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ old('fecha_ultimo_abono') }}">
                    </div>
                </div>

                <hr class="my-4">
                
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> Guardar Cliente y Registrar Venta
                </button>
                <a href="{{ route('registro.index') }}" class="btn btn-secondary btn-lg">Cancelar</a>
            </form>
            
        </div>
    </div>

@endsection 

@section('scripts')
<script>
$(document).ready(function() {

    function calcularCuota() {
        var monto = parseFloat($('#monto_lote').val()) || 0;
        var plazo = parseInt($('#plazo_cuotas').val()) || 0;
        var prima = parseFloat($('#primer_abono').val()) || 0;

        // La prima cuenta como el primer pago del plazo total; el resto del
        // saldo se reparte entre los meses restantes para que llegue a $0.
        if (monto > 0 && plazo > 1) {
            var cuota = (monto - prima) / (plazo - 1);
            $('#cuotas').val((cuota > 0 ? cuota : 0).toFixed(2));
        }
    }

    $('#proyecto_select').change(function() {
        var proyecto = $(this).val();
        var bloqueSelect = $('#bloque_select');
        var loteSelect = $('#lote_select');

        bloqueSelect.html('<option value="">Cargando bloques...</option>').prop('disabled', true);
        loteSelect.html('<option value="">Seleccione un Bloque primero</option>').prop('disabled', true);
        $('#extension_lote').val('');
        $('#extension_lote_value').val('');
        $('#monto_lote').val('');
        calcularCuota();

        if (proyecto) {
            var ajaxUrl = '{{ url("api/proyectos") }}' + '/' + encodeURIComponent(proyecto) + '/bloques';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    bloqueSelect.html('<option value="">Seleccione Bloque</option>');

                    if (data.length > 0) {
                        $.each(data, function(key, bloque) {
                            bloqueSelect.append('<option value="' + bloque.id_bloque + '">B-' + bloque.nombre + '</option>');
                        });
                        bloqueSelect.prop('disabled', false);
                    } else {
                        bloqueSelect.html('<option value="">No hay bloques en este proyecto</option>');
                    }
                },
                error: function(xhr, status, error) {
                    bloqueSelect.html('<option value="">Error al cargar bloques</option>');
                    console.error("AJAX Error:", error, status, xhr.responseText);
                }
            });
        } else {
            bloqueSelect.html('<option value="">Seleccione un Proyecto primero</option>');
        }
    });

    $('#bloque_select').change(function() {
        var bloqueId = $(this).val();
        var loteSelect = $('#lote_select');

        loteSelect.html('<option value="">Cargando lotes...</option>').prop('disabled', true);
        $('#extension_lote').val('');
        $('#extension_lote_value').val('');
        $('#monto_lote').val('');
        calcularCuota();

        if (bloqueId) {
            var ajaxUrl = '{{ url("api/bloques") }}' + '/' + bloqueId + '/lotes';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    loteSelect.html('<option value="">Seleccione uno o más Lotes</option>');

                    if (data.length > 0) {
                        $.each(data, function(key, lote) {
                            loteSelect.append('<option value="' + lote.id_lote + '" data-extension="' + lote.area_metros + '" data-precio="' + lote.precio_base + '">' + lote.numero_lote + '</option>');
                        });
                        loteSelect.prop('disabled', false);
                    } else {
                        loteSelect.html('<option value="">No hay lotes disponibles en este bloque</option>');
                    }
                },
                error: function(xhr, status, error) {
                    loteSelect.html('<option value="">Error al cargar lotes</option>');
                    console.error("AJAX Error:", error, status, xhr.responseText);
                }
            });
        }
    });

    $('#lote_select').change(function() {
        var totalExtension = 0;
        var totalMonto = 0;

        $(this).find('option:selected').each(function() {
            var extension = parseFloat($(this).data('extension'));
            var precio = parseFloat($(this).data('precio'));
            if (!isNaN(extension)) {
                totalExtension += extension;
            }
            if (!isNaN(precio)) {
                totalMonto += precio;
            }
        });

        $('#extension_lote').val(totalExtension.toFixed(2) + ' vrs2');
        $('#extension_lote_value').val(totalExtension.toFixed(2));
        $('#monto_lote').val(totalMonto.toFixed(2));
        calcularCuota();
    });

    // El monto, el plazo y la prima siguen siendo editables: recalculan la
    // cuota sugerida, pero el usuario puede ajustarla manualmente después.
    $('#monto_lote, #plazo_cuotas, #primer_abono').on('input', calcularCuota);
});
</script>
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