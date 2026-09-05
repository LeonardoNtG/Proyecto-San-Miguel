@extends("template")

@section("titulo", "Importación Masiva de Clientes")

@section("contenido")
{{-- ENCABEZADO --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1 text-gray-800 fw-bold">
            <i class="fas fa-file-import text-primary me-2"></i> Importación Masiva de Clientes
        </h1>
        <p class="text-muted small mb-0">
            Migración de cartera histórica desde sistema anterior &middot; Formato Excel (.xlsx)
        </p>
    </div>
    <div>
        <a href="{{ route('importacion.plantilla') }}" class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Descargar Plantilla Excel
        </a>
    </div>
</div>

@if(session("error"))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session("error") }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session("show_result"))
@php
    $errores      = session("import_errores", []);
    $advertencias = session("import_advertencias", []);
    $resumen      = session("import_resumen", []);
    $modo         = session("import_modo", "validar");
    $exitoso      = session("import_exitoso", false);
@endphp
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header py-3 {{ $exitoso && empty($errores) ? 'bg-success' : 'bg-danger' }} text-white d-flex align-items-center">
        @if($exitoso && empty($errores))
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <span class="font-weight-bold">{{ $modo === "importar" ? "¡Importación completada exitosamente!" : "✔ Validación sin errores. El archivo está listo para importar." }}</span>
        @else
            <i class="fas fa-times-circle fa-lg me-2"></i>
            <span class="font-weight-bold">Se encontraron {{ count($errores) }} error(es) que deben corregirse en el archivo.</span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 mb-2">
                <div class="text-center p-3 rounded" style="background:#eef2ff; border:1px solid #c7d2fe;">
                    <div class="h3 font-weight-bold text-primary mb-0">{{ $resumen["clientes_nuevos"] ?? 0 }}</div>
                    <div class="small text-muted font-weight-bold">Clientes Nuevos</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-center p-3 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                    <div class="h3 font-weight-bold text-success mb-0">{{ $resumen["clientes_existentes"] ?? 0 }}</div>
                    <div class="small text-muted font-weight-bold">Clientes Existentes</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-center p-3 rounded" style="background:#ecfeff; border:1px solid #a5f3fc;">
                    <div class="h3 font-weight-bold text-info mb-0">{{ $resumen["contratos"] ?? 0 }}</div>
                    <div class="small text-muted font-weight-bold">Contratos Procesados</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-center p-3 rounded" style="background:#fffbeb; border:1px solid #fde68a;">
                    <div class="h3 font-weight-bold text-warning mb-0">{{ $resumen["pagos"] ?? 0 }}</div>
                    <div class="small text-muted font-weight-bold">Pagos Registrados</div>
                </div>
            </div>
        </div>

        @if(!empty($errores))
        <div class="mb-4">
            <h6 class="font-weight-bold text-danger mb-2"><i class="fas fa-times-circle me-1"></i> Errores encontrados ({{ count($errores) }}):</h6>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light"><tr><th style="width:50px;" class="text-center">#</th><th>Detalle del Error</th></tr></thead>
                    <tbody>
                        @foreach($errores as $idx => $err)
                        <tr>
                            <td class="text-center font-weight-bold text-danger">{{ $idx + 1 }}</td>
                            <td class="small font-weight-bold text-danger">{{ $err }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(!empty($advertencias))
        <div class="mb-3">
            <h6 class="font-weight-bold text-warning mb-2"><i class="fas fa-exclamation-triangle me-1"></i> Advertencias de consistencia ({{ count($advertencias) }}):</h6>
            <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light"><tr><th style="width:50px;" class="text-center">#</th><th>Detalle</th></tr></thead>
                    <tbody>
                        @foreach($advertencias as $idx => $adv)
                        <tr>
                            <td class="text-center font-weight-bold text-warning">{{ $idx + 1 }}</td>
                            <td class="small text-dark">{{ $adv }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($modo === "validar" && $exitoso && empty($errores))
        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3 shadow-sm border-0">
            <div>
                <div class="font-weight-bold"><i class="fas fa-check-double me-1"></i> ¡El archivo pasó todas las validaciones!</div>
                <div class="small">Vuelve a seleccionar el archivo y pulsa <strong>Importar Definitivamente</strong> para guardar los datos.</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- TARJETA DE FORMULARIO DE CARGA --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-upload me-2"></i> Cargar Archivo de Importación
        </h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="font-weight-bold mb-2"><i class="fas fa-info-circle me-1"></i> Instrucciones de migración:</div>
            <ul class="mb-0 small pl-3">
                <li>Asegúrate de que los <strong>Bloques</strong> del proyecto ya existan registrados en el sistema.</li>
                <li>El archivo debe contener las hojas: <code>CLIENTES_CONTRATOS</code>, <code>HISTORIAL_PAGOS</code> y opcionalmente <code>CATALOGO_LOTES</code>.</li>
                <li>Recomendamos ejecutar primero en modo <strong>"1° Solo Validar"</strong> para revisar que no haya incongruencias antes de guardar.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('importacion.procesar') }}" enctype="multipart/form-data" id="formImportacion">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold small text-gray-700" for="sel_lotificacion">
                        <i class="fas fa-map-marked-alt text-primary me-1"></i> Proyecto Destino:
                    </label>
                    <select name="lotificacion_id" id="sel_lotificacion" class="form-control" required>
                        <option value="">-- Seleccionar Proyecto --</option>
                        @foreach($lotificaciones as $lot)
                            <option value="{{ $lot->id }}" {{ old('lotificacion_id', session('import_lotificacion_id')) == $lot->id ? 'selected' : '' }}>
                                {{ $lot->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="font-weight-bold small text-gray-700" for="inp_archivo">
                        <i class="fas fa-file-excel text-success me-1"></i> Archivo Excel (.xlsx):
                    </label>
                    <input type="file" name="archivo" id="inp_archivo" class="form-control-file border p-1 rounded w-100 bg-light" accept=".xlsx,.xls,.ods" required>
                    <small class="text-muted">Formato .xlsx de Excel (Máximo 20 MB).</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold small text-gray-700">
                        <i class="fas fa-cog text-secondary me-1"></i> Modo de Ejecución:
                    </label>
                    <div class="mt-1">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="modo_validar" name="modo" value="validar" class="custom-control-input" checked>
                            <label class="custom-control-label small font-weight-bold text-primary" for="modo_validar">
                                1° Solo Validar (Sin Guardar)
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="modo_importar" name="modo" value="importar" class="custom-control-input">
                            <label class="custom-control-label small font-weight-bold text-danger" for="modo_importar">
                                2° Importar Definitivamente
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ZONA DRAG & DROP --}}
            <div id="dropZone" class="rounded p-4 text-center my-3" style="border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.2s;">
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                <div class="text-muted small">Arrastra tu archivo Excel aquí o haz clic para seleccionarlo</div>
                <div id="nombreArchivo" class="mt-2 font-weight-bold text-primary" style="display:none;"></div>
            </div>

            {{-- BARRA DE PROGRESO --}}
            <div id="progressContainer" class="mt-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span id="progressLabel">Procesando archivo...</span>
                    <span id="progressPct">0%</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                <a href="{{ route('registro.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Clientes
                </a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnProcesar">
                    <i class="fas fa-play-circle me-1"></i> Procesar Archivo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section("scripts")
<script>
$(document).ready(function() {
    var dz = document.getElementById("dropZone");
    var ia = document.getElementById("inp_archivo");
    var na = document.getElementById("nombreArchivo");

    if (dz && ia) {
        dz.addEventListener("click", function() { ia.click(); });
        dz.addEventListener("dragover", function(e) {
            e.preventDefault();
            dz.style.background = "#eef2ff";
            dz.style.borderColor = "#4f46e5";
        });
        dz.addEventListener("dragleave", function() {
            dz.style.background = "#f8fafc";
            dz.style.borderColor = "#cbd5e1";
        });
        dz.addEventListener("drop", function(e) {
            e.preventDefault();
            dz.style.background = "#f8fafc";
            dz.style.borderColor = "#cbd5e1";
            if (e.dataTransfer.files.length > 0) {
                ia.files = e.dataTransfer.files;
                mostrarNombre(e.dataTransfer.files[0].name);
            }
        });
        ia.addEventListener("change", function() {
            if (ia.files && ia.files.length > 0) {
                mostrarNombre(ia.files[0].name);
            }
        });
    }

    function mostrarNombre(nombre) {
        if (na) {
            na.textContent = "📄 " + nombre;
            na.style.display = "block";
        }
        if (dz) {
            dz.style.borderColor = "#10b981";
            dz.style.background = "#f0fdf4";
        }
    }

    var form = document.getElementById("formImportacion");
    if (form) {
        form.addEventListener("submit", function() {
            var btn = document.getElementById("btnProcesar");
            var pc = document.getElementById("progressContainer");
            var pb = document.getElementById("progressBar");
            var pl = document.getElementById("progressLabel");
            var pp = document.getElementById("progressPct");

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
            }
            if (pc) pc.style.display = "block";

            var etapas = [
                { p: 20, l: "Leyendo archivo Excel..." },
                { p: 45, l: "Validando estructura y lotes..." },
                { p: 70, l: "Verificando clientes y contratos..." },
                { p: 90, l: "Procesando historial de pagos..." },
                { p: 98, l: "Generando resumen final..." }
            ];
            var i = 0;
            setInterval(function() {
                if (i < etapas.length) {
                    if (pb) pb.style.width = etapas[i].p + "%";
                    if (pl) pl.textContent = etapas[i].l;
                    if (pp) pp.textContent = etapas[i].p + "%";
                    i++;
                }
            }, 700);
        });
    }
});
</script>
@endsection
