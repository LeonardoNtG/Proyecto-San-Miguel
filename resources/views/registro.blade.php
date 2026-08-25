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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-plus text-primary"></i> Registro de Cliente y Promesa de Venta</h1>
</div>

<form action="{{ route('registro.store') }}" method="POST">
    @csrf

    {{-- SECCIÓN 1: DATOS PERSONALES DEL CLIENTE --}}
    <div class="card shadow-sm border-left-primary mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user"></i> Datos Personales y de Contacto</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-5 mb-3">
                    <label for="nombre_completo" class="form-label font-weight-bold text-secondary">Nombre Completo / Representante</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombres_apellidos" value="{{ old('nombres_apellidos') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cedula" class="form-label font-weight-bold text-secondary">Cédula</label>
                    <input type="text" class="form-control" id="cedula" name="identificacion" value="{{ old('identificacion') }}" placeholder="000-000000-0000A" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="pv_num" class="form-label font-weight-bold text-secondary">N° PV</label>
                    <input type="text" class="form-control" id="pv_num" name="pv_num" value="{{ old('pv_num') }}" placeholder="PV-001" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="expediente_num" class="form-label font-weight-bold text-secondary">N° Expediente</label>
                    <input type="text" class="form-control" id="expediente_num" name="expediente_num" value="{{ old('expediente_num') }}" placeholder="EXP-005" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="telefono" class="form-label font-weight-bold text-secondary">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="+505 0000-0000">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="estado_civil" class="form-label font-weight-bold text-secondary">Estado Civil</label>
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
                    <label for="oficio" class="form-label font-weight-bold text-secondary">Oficio</label>
                    <input type="text" class="form-control" id="oficio" name="oficio" value="{{ old('oficio') }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label font-weight-bold text-secondary">Dirección Exacta</label>
                <textarea class="form-control" id="direccion" name="direccion" rows="2" placeholder="Ingrese la dirección completa del cliente">{{ old('direccion') }}</textarea>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: DATOS DE LA VENTA / LOTE --}}
    <div class="card shadow-sm border-left-info mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-map-marked-alt"></i> Selección de Lotes</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                {{-- Proyecto (Select) --}}
                <div class="col-md-4 mb-3">
                    <label for="proyecto_select" class="form-label font-weight-bold text-secondary">1. Seleccione Proyecto</label>
                    <select class="form-select border-info" id="proyecto_select" name="lotificacion_id" required>
                        <option value="">-- Escoger Proyecto --</option>
                        @isset($proyectos)
                            @foreach ($proyectos as $proyecto)
                                <option value="{{ $proyecto->id }}" @selected(old('lotificacion_id') == $proyecto->id)>{{ $proyecto->nombre }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>

                {{-- Bloque (Select dinámico) --}}
                <div class="col-md-4 mb-3">
                    <label for="bloque" class="form-label font-weight-bold text-secondary">2. Seleccione Bloque</label>
                    <select class="form-select border-info" id="bloque_select" name="bloque_id" required disabled>
                        <option value="">Seleccione un Proyecto primero</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="lotes_seleccionados" class="form-label font-weight-bold text-secondary">3. Lotes a Vender (Máx. 20)</label>
                    <select class="form-select border-info shadow-sm" id="lote_select" name="lotes_ids[]" multiple required disabled style="min-height: 120px;">
                        <option value="">Seleccione un Bloque primero</option>
                    </select>
                    <small class="text-muted">Mantenga presionada la tecla Ctrl (Windows) o Command (Mac) para seleccionar varios lotes.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 3: PLAN DE PAGOS --}}
    <div class="card shadow-sm border-left-success mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-hand-holding-usd"></i> Detalles Financieros (Plan de Pagos)</h6>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3">
                <div class="col-md-3 mb-3">
                    <label for="extension" class="form-label font-weight-bold text-secondary">Extensión TOTAL</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white" id="extension_lote" name="extension" placeholder="0.00" readonly required>
                        <span class="input-group-text">m²</span>
                    </div>
                    <input type="hidden" id="extension_lote_value" name="extension_value">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="monto_lote" class="form-label font-weight-bold text-secondary">Precio Venta (Total)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold text-success" id="monto_lote" name="precio_final" placeholder="0.00" value="{{ old('precio_final') }}" required>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="primer_abono" class="form-label font-weight-bold text-secondary">Prima / Enganche</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" class="form-control border-success" id="primer_abono" name="primer_abono" placeholder="0.00" value="{{ old('primer_abono') }}" required>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="fecha_ultimo_abono" class="form-label font-weight-bold text-secondary">Fecha del 1° Pago</label>
                    <input type="date" class="form-control" id="fecha_ultimo_abono" name="fecha_ultimo_abono" value="{{ old('fecha_ultimo_abono') }}">
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="plazo_cuotas" class="form-label font-weight-bold text-secondary">Plazo de Financiamiento</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="plazo_cuotas" name="plazo_meses" value="{{ old('plazo_meses') }}" required placeholder="Ej: 60">
                        <span class="input-group-text">Meses</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cuotas" class="form-label font-weight-bold text-secondary">Cuota Mensual Sugerida</label>
                    <div class="input-group">
                        <span class="input-group-text text-danger font-weight-bold">$</span>
                        <input type="number" step="0.01" class="form-control font-weight-bold" style="font-size: 1.1rem; color: #e74a3b;" id="cuotas" name="cuota_mensual" placeholder="0.00" value="{{ old('cuota_mensual') }}" required>
                    </div>
                    <small class="text-muted">Monto recalculado automáticamente.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <a href="{{ route('registro.index') }}" class="btn btn-secondary btn-lg mr-3 shadow-sm"><i class="fas fa-times"></i> Cancelar</a>
        <button type="submit" class="btn btn-success btn-lg shadow-sm px-5"><i class="fas fa-check-circle"></i> Confirmar y Guardar Venta</button>
    </div>
</form>

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
            var ajaxUrl = '{{ url("api/lotificaciones") }}' + '/' + encodeURIComponent(proyecto) + '/bloques';

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