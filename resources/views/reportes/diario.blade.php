@extends('template') {{-- Hereda la plantilla principal --}}

@section('titulo', 'Inicio') {{-- Define el contenido de la sección 'titulo' --}}

@section('contenido') {{-- Abre la sección principal 'contenido' --}}

    <h1>Reportes Generales</h1>

<hr>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card mb-4 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 text-center">
            <thead class="bg-light">
                <tr>
                    <th>Efectivo Anterior (Base)</th>
                    <th>Ingresado Hoy</th>
                    <th class="text-primary">Efectivo Total (Suma)</th>
                    <th class="text-danger">Salidas / Gastos</th>
                    <th class="bg-dark text-white">Saldo en Caja</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="align-middle">${{ number_format($saldoInicial, 2) }}</td>
                    <td class="align-middle text-success">+ ${{ number_format($ingresosHoy, 2) }}</td>
                    <td class="align-middle fw-bold">${{ number_format($saldoInicial + $ingresosHoy, 2) }}</td>
                    <td class="align-middle text-danger">- ${{ number_format($egresosHoy, 2) }}</td>
                    <td class="align-middle fw-bold fs-5">${{ number_format($saldoFinalCaja, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-3">
    @if(!$cajaAbierta)
        <button type="button" class="btn btn-warning fw-bold px-4 text-dark" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
            <i class="fas fa-box-open"></i> Abrir Caja
        </button>
    @elseif($cajaAbierta && !$cajaCerrada)
        <button type="button" class="btn btn-danger text-white" data-bs-toggle="modal" data-bs-target="#modalSalida">
            <i class="fas fa-minus-circle"></i> Registrar Salida
        </button>
        <button type="button" class="btn btn-dark text-white" data-bs-toggle="modal" data-bs-target="#modalCerrarCaja">
            <i class="fas fa-lock"></i> Realizar Cierre de Caja
        </button>
    @else
        <button type="button" class="btn btn-secondary text-white" disabled title="Último turno cerrado">
            <i class="fas fa-lock"></i> Turno Cerrado
        </button>
        @if(isset($cierresHoy) && $cierresHoy->isNotEmpty())
        <a href="{{ route('reportes.cierre_turno.pdf', $cierresHoy->first()->id) }}" target="_blank" class="btn btn-outline-dark fw-bold">
            <i class="fas fa-print"></i> Imprimir Reporte
        </a>
        @endif
        <button type="button" class="btn btn-warning fw-bold px-4 text-dark" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
            <i class="fas fa-box-open"></i> Abrir Nuevo Turno
        </button>
    @endif
</div>

@if(!$cajaAbierta)
    <div class="alert alert-warning border-warning shadow-sm text-dark">
        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Atención:</strong> Debe <a href="#" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja" class="text-dark fw-bold text-decoration-underline">Abrir la Caja</a> para poder iniciar su turno y registrar movimientos.
    </div>
@elseif($cajaCerrada)
    <div class="alert alert-info border-info shadow-sm text-dark">
        <i class="fas fa-info-circle me-2"></i> <strong>Turno Finalizado:</strong> El turno anterior ha sido cerrado. Abra un <strong>Nuevo Turno</strong> si desea continuar registrando transacciones hoy.
    </div>
@endif

{{-- Formulario para ver las salidas  --}}
<div class="card">
    <div class="card-header bg-white">
        <strong>Detalle de Movimientos (Salidas)</strong>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Descripción / Motivo</th>
                    <th>Método de Pago</th>
                    <th class="text-end">Monto</th>
                    <th style="width: 80px;" class="text-center">Opciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listaSalidas as $salida)
                    <tr>
                        <td>{{ $salida->created_at->format('H:i A') }}</td>
                        <td>{{ $salida->descripcion }}</td>
                        <td><span class="badge bg-secondary">{{ $salida->metodo_pago ?? 'Efectivo' }}</span></td>
                        <td class="text-end text-danger">- ${{ number_format($salida->monto, 2) }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#modalEliminarSalida{{ $salida->id }}"
                                    title="Eliminar salida">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No hay salidas registradas hoy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(isset($cierresHoy) && $cierresHoy->isNotEmpty())
<div class="card mt-4 shadow-sm border-0 border-top border-dark border-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-archive text-secondary"></i> Turnos Cerrados el Día de Hoy</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Hora de Cierre</th>
                    <th>Efectivo Reportado</th>
                    <th>Diferencia</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cierresHoy as $cierre)
                <tr>
                    <td class="align-middle">{{ $cierre->created_at->format('h:i A') }}</td>
                    <td class="align-middle">${{ number_format($cierre->efectivo_real, 2) }}</td>
                    <td class="align-middle">
                        @if($cierre->diferencia == 0)
                            <span class="badge bg-success">Cuadrado</span>
                        @elseif($cierre->diferencia > 0)
                            <span class="badge bg-warning text-dark">+${{ number_format($cierre->diferencia, 2) }}</span>
                        @else
                            <span class="badge bg-danger">-${{ number_format(abs($cierre->diferencia), 2) }}</span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        <a href="{{ route('reportes.cierre_turno.pdf', $cierre->id) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Imprimir PDF del Cierre">
                            <i class="fas fa-file-pdf"></i> Imprimir Reporte
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Modal para Abrir Caja --}}
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-labelledby="modalAbrirCajaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('reportes.abrirCaja') }}" method="POST">
            @csrf
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="modalAbrirCajaLabel">Abrir Caja Diaria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Con cuánto efectivo inicia la caja el día de hoy?</p>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Monto de Apertura <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" name="monto_inicial" id="monto_inicial" class="form-control" value="{{ old('monto_inicial', $saldoInicial) }}" required>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> No se permiten valores negativos. Ingrese $0.00 o el monto con el que abre caja.</small>
                    </div>
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-box-open me-1"></i> Abrir Caja
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal para registrar una salida --}}
<div class="modal fade" id="modalSalida" tabindex="-1" aria-labelledby="modalSalidaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('reportes.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalSalidaLabel">Registrar Salida de Efectivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Monto a retirar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0.01" name="monto" id="input_monto_salida" class="form-control" placeholder="0.00" required>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> No se permiten valores negativos ni cero. Ingrese un monto positivo.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo de la salida</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Ej: Pago de recibo de luz, compra de papelería..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="Efectivo" selected>Efectivo (Caja)</option>
                            <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check me-1"></i> Registrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal para confirmar el Cierre de Caja --}}
<div class="modal fade" id="modalCerrarCaja" tabindex="-1" aria-labelledby="modalCerrarCajaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('reportes.cerrarCaja') }}" method="POST" id="formCerrarCaja">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalCerrarCajaLabel">Realizar Cierre de Caja (Arqueo)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Se cerrará la caja del día <strong>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</strong>.</p>
                    
                    <div class="card mb-3 bg-light">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between">
                                <span>Base Inicial:</span>
                                <span>${{ number_format($saldoInicial, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-success" data-bs-toggle="collapse" data-bs-target="#collapseIngresos" style="cursor: pointer;" title="Clic para ver detalle">
                                <span><i class="fas fa-chevron-down me-1" style="font-size: 0.8em;"></i> Ingresos (+):</span>
                                <span>${{ number_format($ingresosHoy, 2) }}</span>
                            </div>
                            <div class="collapse" id="collapseIngresos">
                                <div class="bg-white border rounded p-2 mb-2 mt-1 small">
                                    @forelse($listaIngresos as $ingreso)
                                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                            <span>
                                                <i class="fas fa-caret-right me-1 text-success"></i>
                                                {{ $ingreso->metodo_pago }} - {{ Str::limit(optional($ingreso->venta->cliente)->nombre1 . ' ' . optional($ingreso->venta->cliente)->apellido1, 15, '...') }}
                                            </span>
                                            <span class="text-success">${{ number_format($ingreso->monto_abonado, 2) }}</span>
                                        </div>
                                    @empty
                                        <div class="text-muted fst-italic">Sin ingresos registrados.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="d-flex justify-content-between text-danger mt-1" data-bs-toggle="collapse" data-bs-target="#collapseEgresos" style="cursor: pointer;" title="Clic para ver detalle">
                                <span><i class="fas fa-chevron-down me-1" style="font-size: 0.8em;"></i> Salidas (-):</span>
                                <span>${{ number_format($egresosHoy, 2) }}</span>
                            </div>
                            <div class="collapse" id="collapseEgresos">
                                <div class="bg-white border rounded p-2 mb-2 mt-1 small">
                                    @forelse($listaSalidas as $salida)
                                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                            <span>
                                                <i class="fas fa-caret-right me-1 text-danger"></i>
                                                {{ $salida->metodo_pago }} - {{ Str::limit($salida->descripcion, 15, '...') }}
                                            </span>
                                            <span class="text-danger">${{ number_format($salida->monto, 2) }}</span>
                                        </div>
                                    @empty
                                        <div class="text-muted fst-italic">Sin salidas registradas.</div>
                                    @endforelse
                                </div>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Saldo Esperado:</span>
                                <span>$<span id="saldoEsperadoText">{{ number_format($saldoFinalCaja, 2) }}</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Efectivo Real en Caja <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" name="efectivo_real" id="efectivo_real" class="form-control" placeholder="0.00" required>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Ingrese el dinero físico en gaveta (no se permiten números negativos).</small>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Diferencia:</span>
                            <span id="diferenciaSpan" class="text-secondary">$0.00</span>
                        </div>
                    </div>

                    <div class="mb-3" id="comentarioGroup" style="display: none;">
                        <label class="form-label fw-bold text-danger">Justificación obligatoria <span id="tipoDiferencia"></span></label>
                        <textarea name="comentario" id="comentario" class="form-control" rows="2" placeholder="Explica el motivo del descuadre..."></textarea>
                    </div>

                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                    <input type="hidden" id="saldoFinalCaja" value="{{ $saldoFinalCaja }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark" id="btnConfirmarCierre">
                        <i class="fas fa-lock me-1"></i> Confirmar Cierre
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modales de confirmación para eliminar una salida --}}
@foreach ($listaSalidas as $salida)
    <div class="modal fade" id="modalEliminarSalida{{ $salida->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta salida?</p>
                    <p class="mb-1"><strong>Motivo:</strong> {{ $salida->descripcion }}</p>
                    <p class="text-danger mb-0"><strong>Monto:</strong> ${{ number_format($salida->monto, 2) }}</p>
                    <p class="text-muted small mt-2 mb-0">Esta acción es irreversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('reportes.destroy', $salida->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Sí, eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection

@section('scripts')
    <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- scripts para todas las paginas-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Paginas -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bloqueo global de signos negativos y letras en inputs tipo número
            document.querySelectorAll('input[type=number]').forEach(function(input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === '-' || e.key === '+' || e.key === 'e' || e.key === 'E') {
                        e.preventDefault();
                    }
                });
                input.addEventListener('input', function() {
                    if (this.value && parseFloat(this.value) < 0) {
                        this.value = '';
                    }
                });
            });

            const efectivoRealInput = document.getElementById('efectivo_real');
            const saldoEsperado = parseFloat(document.getElementById('saldoFinalCaja').value);
            const diferenciaSpan = document.getElementById('diferenciaSpan');
            const comentarioGroup = document.getElementById('comentarioGroup');
            const comentarioInput = document.getElementById('comentario');
            const tipoDiferencia = document.getElementById('tipoDiferencia');
            const formCerrarCaja = document.getElementById('formCerrarCaja');

            if (efectivoRealInput) {
                efectivoRealInput.addEventListener('input', function() {
                    const efectivoReal = parseFloat(this.value);
                    
                    if (isNaN(efectivoReal)) {
                        diferenciaSpan.textContent = '$0.00';
                        diferenciaSpan.className = 'text-secondary';
                        comentarioGroup.style.display = 'none';
                        comentarioInput.required = false;
                        return;
                    }

                    const diferencia = efectivoReal - saldoEsperado;
                    
                    // Formatear moneda
                    const diffFormatted = Math.abs(diferencia).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    
                    if (Math.abs(diferencia) < 0.01) {
                        diferenciaSpan.textContent = 'Cuadrado ($0.00)';
                        diferenciaSpan.className = 'text-success fw-bold';
                        comentarioGroup.style.display = 'none';
                        comentarioInput.required = false;
                    } else if (diferencia > 0) {
                        diferenciaSpan.textContent = 'Sobrante (+$' + diffFormatted + ')';
                        diferenciaSpan.className = 'text-warning fw-bold';
                        comentarioGroup.style.display = 'block';
                        tipoDiferencia.textContent = '(por sobrante)';
                        comentarioInput.required = true;
                    } else {
                        diferenciaSpan.textContent = 'Faltante (-$' + diffFormatted + ')';
                        diferenciaSpan.className = 'text-danger fw-bold';
                        comentarioGroup.style.display = 'block';
                        tipoDiferencia.textContent = '(por faltante)';
                        comentarioInput.required = true;
                    }
                });
            }

            if (formCerrarCaja) {
                formCerrarCaja.addEventListener('submit', function(e) {
                    const efectivoReal = parseFloat(efectivoRealInput.value);
                    const diferencia = efectivoReal - saldoEsperado;
                    
                    if (Math.abs(diferencia) >= 0.01 && comentarioInput.value.trim() === '') {
                        e.preventDefault();
                        alert('Debe proporcionar una justificación obligatoria debido a la diferencia en caja.');
                        comentarioInput.focus();
                    }
                });
            }
        });
    </script>
@endsection
