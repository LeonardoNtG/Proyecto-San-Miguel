@extends('template')

@section('titulo', 'Parámetros del Sistema')

@section('contenido')

<style>
    .config-tab-btn {
        font-weight: 600;
        font-size: 0.95rem;
        padding: 12px 20px;
        border-radius: 8px;
        color: #4a5568;
        border: none;
        background: transparent;
        transition: all 0.2s ease;
    }
    .config-tab-btn.active {
        background-color: #4e73df !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.25);
    }
    .param-card {
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        transition: all 0.2s ease;
        background: #ffffff;
    }
    .param-card:hover {
        border-color: #4e73df;
        box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }
    .project-badge-header {
        background: linear-gradient(135deg, #2e384d 0%, #1a202c 100%);
        border-radius: 12px;
        color: #ffffff;
    }
</style>

<div class="container-fluid py-3">

    {{-- ALERTAS DE SESIÓN --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- CABECERA CON SELECTOR DE PROYECTO --}}
    <div class="card project-badge-header shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="mb-1 fw-bold text-white">
                        <i class="fas fa-sliders-h text-warning me-2"></i> Parámetros y Reglas del Sistema
                    </h3>
                    <p class="text-white-50 mb-0 small">
                        Personaliza el comportamiento operativo, financiero y de cobro de cada proyecto sin modificar código.
                    </p>
                </div>

                {{-- Selector de Proyecto a Configurar --}}
                <div class="bg-white bg-opacity-10 p-2 rounded-3 d-flex align-items-center gap-2 border border-white border-opacity-25">
                    <span class="text-white small fw-bold text-nowrap"><i class="fas fa-map-marked-alt text-warning me-1"></i> Proyecto:</span>
                    <form method="GET" action="{{ route('configuracion.parametros.index') }}" id="formSelectProyecto" class="m-0">
                        <select name="lotificacion_id" class="form-select form-select-sm fw-bold border-0 shadow-sm" onchange="this.form.submit()" style="min-width: 220px;">
                            @foreach($lotificaciones as $lot)
                                <option value="{{ $lot->id }}" @selected($targetLotificacionId == $lot->id)>
                                    {{ $lot->nombre }} {{ $lot->id == session('lotificacion_id') ? '(Activo)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- FORMULARIO PRINCIPAL DE PARÁMETROS --}}
    <form action="{{ route('configuracion.parametros.update') }}" method="POST">
        @csrf
        <input type="hidden" name="lotificacion_id" value="{{ $targetLotificacionId }}">

        {{-- NAVEGACIÓN POR PESTAÑAS --}}
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-2">
                <ul class="nav nav-pills nav-fill gap-2" id="pills-tab" role="tablist">
                    @foreach($grupos as $grupoKey => $grupoData)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link config-tab-btn {{ $loop->first ? 'active' : '' }}" 
                                id="pills-{{ $grupoKey }}-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#pills-{{ $grupoKey }}" 
                                type="button" 
                                role="tab">
                            <i class="{{ $grupoData['icono'] }} me-2"></i> {{ $grupoData['titulo'] }}
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- CONTENIDO DE LAS PESTAÑAS --}}
        <div class="tab-content" id="pills-tabContent">
            @foreach($grupos as $grupoKey => $grupoData)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pills-{{ $grupoKey }}" role="tabpanel">
                
                <div class="row g-3">
                    @foreach($grupoData['parametros'] as $clave => $param)
                    <div class="col-lg-6 col-12">
                        <div class="card param-card h-100 p-3">
                            
                            @if($param['tipo'] === 'boolean')
                                {{-- SWITCH BOOLEANO --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="pe-3">
                                        <label class="form-check-label fw-bold text-dark fs-6 d-block mb-1" for="switch_{{ $clave }}">
                                            {{ $param['label'] }}
                                        </label>
                                        <p class="text-muted small mb-0">{{ $param['descripcion'] }}</p>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="switch_{{ $clave }}" 
                                               name="{{ $clave }}" 
                                               value="1" 
                                               @checked($param['valor_actual'])>
                                    </div>
                                </div>

                            @elseif(isset($param['options']))
                                {{-- SELECT CON OPCIONES --}}
                                <div>
                                    <label class="form-label fw-bold text-dark mb-1" for="input_{{ $clave }}">
                                        {{ $param['label'] }}
                                    </label>
                                    <p class="text-muted small mb-2">{{ $param['descripcion'] }}</p>
                                    <select class="form-select" id="input_{{ $clave }}" name="{{ $clave }}">
                                        @foreach($param['options'] as $optVal => $optLabel)
                                            <option value="{{ $optVal }}" @selected($param['valor_actual'] == $optVal)>
                                                {{ $optLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            @elseif($param['tipo'] === 'text')
                                {{-- TEXTAREA --}}
                                <div>
                                    <label class="form-label fw-bold text-dark mb-1" for="input_{{ $clave }}">
                                        {{ $param['label'] }}
                                    </label>
                                    <p class="text-muted small mb-2">{{ $param['descripcion'] }}</p>
                                    <textarea class="form-control" id="input_{{ $clave }}" name="{{ $clave }}" rows="2">{{ $param['valor_actual'] }}</textarea>
                                </div>

                            @else
                                {{-- INPUT NUMÉRICO / STRING --}}
                                <div>
                                    <label class="form-label fw-bold text-dark mb-1" for="input_{{ $clave }}">
                                        {{ $param['label'] }}
                                    </label>
                                    <p class="text-muted small mb-2">{{ $param['descripcion'] }}</p>
                                    
                                    @if($clave === 'valor_mora')
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold text-primary" id="addon_mora_tipo">%</span>
                                            <input type="number" step="0.01" class="form-control input-valor-mora fw-bold" 
                                                   id="input_{{ $clave }}" 
                                                   name="{{ $clave }}" 
                                                   value="{{ $param['valor_actual'] }}"
                                                   placeholder="Ej. 5 para 5%">
                                        </div>
                                        <div class="mt-2 p-2 rounded small fw-bold text-success border border-success border-opacity-25" style="background-color: #f0fdf4;" id="box_mora_diaria">
                                            <i class="fas fa-calculator me-1"></i> <span id="txt_mora_diaria">Cálculo de mora</span>
                                        </div>
                                    @else
                                        <input type="{{ $param['tipo'] === 'decimal' || $param['tipo'] === 'integer' ? 'number' : 'text' }}" 
                                               step="{{ $param['tipo'] === 'decimal' ? '0.01' : '1' }}" 
                                               class="form-control" 
                                               id="input_{{ $clave }}" 
                                               name="{{ $clave }}" 
                                               value="{{ $param['valor_actual'] }}">
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                    @endforeach
                </div>

            </div>
            @endforeach
        </div>

        {{-- BOTÓN GUARDAR CAMBIOS --}}
        <div class="card shadow-sm border-0 mt-4 bg-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    <i class="fas fa-info-circle text-primary me-1"></i> Los cambios se aplicarán inmediatamente a todas las operaciones del proyecto <strong>{{ $lotificacionActiva->nombre ?? 'seleccionado' }}</strong>.
                </span>
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                    <i class="fas fa-save me-1"></i> Guardar Parámetros de {{ $lotificacionActiva->nombre ?? 'Proyecto' }}
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputValorMora = document.querySelector('.input-valor-mora');
    const txtMoraDiaria = document.getElementById('txt_mora_diaria');
    const selectTipoMora = document.getElementById('input_tipo_mora');
    const addonMoraTipo = document.getElementById('addon_mora_tipo');

    function actualizarMoraDiaria() {
        if (!inputValorMora || !txtMoraDiaria) return;

        const valor = parseFloat(inputValorMora.value) || 0;
        const esPorcentaje = selectTipoMora && selectTipoMora.value === 'porcentaje';

        if (addonMoraTipo) {
            addonMoraTipo.textContent = esPorcentaje ? '%' : '$';
        }

        if (esPorcentaje) {
            const porcentajeDiario = (valor / 30).toFixed(3);
            const ejemploCuota150 = ((150 * (valor / 100)) / 30).toFixed(2);
            txtMoraDiaria.innerHTML = `Equivalente diario: <strong>${porcentajeDiario}% / día</strong> (Ej. en cuota de $150: <strong>$${ejemploCuota150} / día de atraso</strong>)`;
        } else {
            const diario = (valor / 30).toFixed(2);
            txtMoraDiaria.innerHTML = `Equivalente por día: <strong>$${diario} / día de atraso</strong> ($${valor.toFixed(2)} por mes de 30 días)`;
        }
    }

    if (inputValorMora) {
        inputValorMora.addEventListener('input', actualizarMoraDiaria);
    }
    if (selectTipoMora) {
        selectTipoMora.addEventListener('change', actualizarMoraDiaria);
    }

    actualizarMoraDiaria();
});
</script>
@endsection
