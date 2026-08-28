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
        <button type="button" class="btn btn-warning fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
            <i class="fas fa-box-open"></i> Abrir Caja
        </button>
    @else
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalSalida">
            <i class="fas fa-minus-circle"></i> Registrar Salida
        </button>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalCerrarCaja">
            <i class="fas fa-lock"></i> Realizar Cierre de Caja
        </button>
    @endif
</div>

@if(!$cajaAbierta)
    <div class="alert alert-warning border-warning shadow-sm">
        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Atención:</strong> La caja del día <strong>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</strong> aún no ha sido abierta. Para registrar salidas o realizar el cierre, primero debe <a href="#" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">Abrir la Caja</a>.
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
                        <label class="form-label">Monto de Apertura</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="monto_inicial" class="form-control" value="{{ old('monto_inicial', $saldoInicial) }}" required>
                        </div>
                        <small class="text-muted">Por defecto mostramos lo que se arrastra (si lo hay), pero puedes colocar 0 u otra cantidad.</small>
                    </div>
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
                        <label class="form-label">Monto a retirar</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                        </div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
                            <div class="d-flex justify-content-between text-success">
                                <span>Ingresos (+):</span>
                                <span>${{ number_format($ingresosHoy, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-danger">
                                <span>Salidas (-):</span>
                                <span>${{ number_format($egresosHoy, 2) }}</span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Saldo Esperado:</span>
                                <span>$<span id="saldoEsperadoText">{{ number_format($saldoFinalCaja, 2) }}</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Efectivo Real en Caja</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="efectivo_real" id="efectivo_real" class="form-control" placeholder="0.00" required>
                        </div>
                        <small class="text-muted">Ingresa la cantidad física de dinero que tienes en tu gaveta.</small>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
            const efectivoRealInput = document.getElementById('efectivo_real');
            const saldoEsperado = parseFloat(document.getElementById('saldoFinalCaja').value);
            const diferenciaSpan = document.getElementById('diferenciaSpan');
            const comentarioGroup = document.getElementById('comentarioGroup');
            const comentarioInput = document.getElementById('comentario');
            const tipoDiferencia = document.getElementById('tipoDiferencia');
            const formCerrarCaja = document.getElementById('formCerrarCaja');

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
                
                if (diferencia === 0) {
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

            formCerrarCaja.addEventListener('submit', function(e) {
                const efectivoReal = parseFloat(efectivoRealInput.value);
                const diferencia = efectivoReal - saldoEsperado;
                
                if (diferencia !== 0 && comentarioInput.value.trim() === '') {
                    e.preventDefault();
                    alert('Debe proporcionar una justificación obligatoria debido a la diferencia en caja.');
                    comentarioInput.focus();
                }
            });
        });
    </script>
@endsection
