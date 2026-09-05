@extends('template')

@section('titulo', 'Datos Generales de Clientes (Promesas de Venta)')

@section('contenido')
<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fas fa-file-contract text-primary me-2"></i> Datos Legales para Promesas de Venta
            </h1>
            <p class="text-muted small mb-0">
                Expediente de generales de ley, términos notariales y descripción de inmuebles para formalización legal.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reportes.datos_legales.pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </a>
            <a href="{{ route('reportes.datos_legales.excel', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
        </div>
    </div>

    {{-- TARJETAS DE RESUMEN / KPIS --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Compradores</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalClientes) }} Contratos</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Valor Total Contratado</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalPrecioVentas, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Primas Recaudadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalPrimas, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Área Total Vendida</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalAreaM2, 2) }} m²</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marked-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS DE BÚSQUEDA --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i> Filtros de Búsqueda y Rango de Fechas
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.datos_legales') }}" class="row g-3 align-items-end">
                
                {{-- Selector de Proyecto (Solo Admin) --}}
                @if($esAdmin && isset($proyectosDisponibles) && count($proyectosDisponibles) > 1)
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Proyecto / Lotificación:</label>
                    <select name="proyecto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="actual" @selected($filtroSeleccionado === 'actual')>Proyecto Activo en Sesión</option>
                        <option value="global" @selected($filtroSeleccionado === 'global' || $filtroSeleccionado === 'todos')>🌐 Todos los Proyectos (Consolidado)</option>
                        @foreach($proyectosDisponibles as $p)
                            <option value="{{ $p->id }}" @selected($filtroSeleccionado == $p->id)>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Fecha Inicio --}}
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Fecha Venta (Desde):</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ $fechaInicio }}">
                </div>

                {{-- Fecha Fin --}}
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Fecha Venta (Hasta):</label>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ $fechaFin }}">
                </div>

                {{-- Estado Contrato --}}
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Estado del Contrato:</label>
                    <select name="estado_contrato" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="Todos" @selected($estadoContrato == 'Todos')>Todos los Estados</option>
                        <option value="Vigente" @selected($estadoContrato == 'Vigente')>Vigente</option>
                        <option value="Finalizado" @selected($estadoContrato == 'Finalizado')>Finalizado</option>
                        <option value="Cancelado" @selected($estadoContrato == 'Cancelado')>Cancelado</option>
                    </select>
                </div>

                {{-- Buscador Libre --}}
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Buscador (Nombre / Cédula / PV / Lote):</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar cliente..." value="{{ $buscar }}">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        @if($fechaInicio || $fechaFin || $buscar || $estadoContrato != 'Todos' || ($filtroSeleccionado != 'actual' && $filtroSeleccionado != ''))
                            <a href="{{ route('reportes.datos_legales') }}" class="btn btn-outline-secondary" title="Limpiar"><i class="fas fa-times"></i></a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA DE DATOS LEGALES CON PAGINACIÓN UNIVERSAL --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list-alt me-1"></i> Expediente Notarial de Compradores ({{ count($ventasData) }} registros)
            </h6>
            <div class="small text-muted">
                Proyecto: <span class="badge bg-light text-dark border">{{ $nombreProyecto }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle table-paginada" data-page-size="10">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 130px;">Comprador</th>
                            <th>Identificación</th>
                            <th>Estado Civil / Oficio</th>
                            <th>Contacto / Dirección</th>
                            <th>Inmueble / Lote</th>
                            <th>Área</th>
                            <th>Precio / Prima</th>
                            <th>Condiciones</th>
                            <th>Beneficiario</th>
                            <th class="text-center" style="min-width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasData as $v)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $v['cliente_nombre'] }}</div>
                                <div class="small text-muted">{{ $v['pv_num'] }} &middot; {{ $v['expediente_num'] }}</div>
                                <span class="badge bg-{{ $v['estado_contrato'] == 'Vigente' ? 'success' : ($v['estado_contrato'] == 'Finalizado' ? 'info' : 'secondary') }} text-white" style="font-size: 10px;">
                                    {{ $v['estado_contrato'] }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-monospace">{{ $v['identificacion'] }}</span>
                            </td>
                            <td>
                                <div class="small"><strong>Civil:</strong> {{ $v['estado_civil'] }}</div>
                                <div class="small text-muted"><strong>Oficio:</strong> {{ $v['oficio'] }}</div>
                            </td>
                            <td>
                                <div class="small"><i class="fas fa-phone text-secondary me-1"></i>{{ $v['telefono'] }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 180px;" title="{{ $v['direccion'] }}">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $v['direccion'] }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary text-white">{{ $v['lotes_texto'] }}</span>
                                <div class="small text-muted">{{ $v['proyecto_nombre'] }}</div>
                            </td>
                            <td>
                                <div class="small fw-bold">{{ number_format($v['area_metros'], 2) }} m²</div>
                                <div class="small text-muted">{{ number_format($v['area_varas'], 2) }} v²</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">${{ number_format($v['precio_final'], 2) }}</div>
                                <div class="small text-success">Prima: ${{ number_format($v['prima_pagada'], 2) }}</div>
                            </td>
                            <td>
                                <div class="small"><strong>Plazo:</strong> {{ $v['plazo_meses'] }} meses</div>
                                <div class="small text-primary"><strong>Cuota:</strong> ${{ number_format($v['cuota_mensual'], 2) }}/mes</div>
                                <div class="small text-muted">Venta: {{ $v['fecha_venta_fmt'] }}</div>
                            </td>
                            <td>
                                @if($v['beneficiario_final'])
                                    <div class="small fw-bold text-dark"><i class="fas fa-user-shield text-info me-1"></i>{{ $v['beneficiario_final'] }}</div>
                                    @if($v['nota_beneficiario'])
                                        <div class="small text-muted">{{ $v['nota_beneficiario'] }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('reportes.promesa_venta.imprimir', $v['id_venta']) }}" target="_blank" class="btn btn-outline-primary" title="Imprimir Ficha Técnica para Promesa de Venta">
                                        <i class="fas fa-print me-1"></i> Ficha
                                    </a>
                                    @if($v['id_cliente'])
                                    <a href="{{ route('registro.show', $v['id_cliente']) }}" class="btn btn-outline-secondary" title="Ver Expediente Completo">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                                No se encontraron registros con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="{{ asset('js/reportes-paginacion.js') }}"></script>
@endpush

@endsection
