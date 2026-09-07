<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta - {{ $cliente->nombres_apellidos }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #333;
        }
        .header-brand {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            border-bottom: 5px solid #ffc107;
            text-align: center;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .card-custom-header {
            background-color: #fff;
            border-bottom: 1px solid #edf2f9;
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-custom-header i {
            color: #2a5298;
        }
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .status-badge {
            padding: 0.5em 1em;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .table > :not(caption) > * > * {
            padding: 1rem 0.75rem;
        }
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

    <header class="header-brand">
        <div class="container">
            <h1><i class="fas fa-home"></i> Proyecto San Miguel</h1>
            <p class="mb-0 opacity-75">Portal del Cliente</p>
        </div>
    </header>

    <div class="container mt-4">
        
        <!-- Alerta de Bienvenida -->
        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4" role="alert">
            <h4 class="alert-heading"><i class="fas fa-user-circle me-2"></i>¡Hola, {{ $cliente->nombres_apellidos }}!</h4>
            <p>Bienvenido a tu portal en línea. Aquí puedes visualizar el estado actualizado de tu cuenta, revisar tus pagos y descargar tus comprobantes.</p>
        </div>

        @if(isset($ventas) && $ventas->count() > 1)
        <!-- Selector de Contratos Múltiples -->
        <div class="card card-custom mb-4 border-0 shadow-sm">
            <div class="card-body p-3 bg-white">
                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-layer-group me-1"></i> Tus Contratos / Lotes ({{ $ventas->count() }}):</h6>
                <div class="row g-2">
                    @foreach($ventas as $v)
                        @php
                            $lotesV = $v->lotes;
                            $nombreLotes = $lotesV->map(fn($l) => $l->nombre_completo)->implode(', ');
                            $esActual = ($venta && $venta->id_venta == $v->id_venta);
                        @endphp
                        <div class="col-md-4">
                            <a href="{{ route('portal.estado_cuenta', [$cliente->token_seguimiento, 'venta_id' => $v->id_venta]) }}" class="text-decoration-none">
                                <div class="p-2 rounded border {{ $esActual ? 'border-primary bg-primary text-white shadow-sm' : 'border-secondary-subtle bg-light text-dark' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="fs-6">{{ $nombreLotes ?: 'Contrato #'.$v->id_venta }}</strong>
                                        <span class="badge {{ $esActual ? ($v->estado_contrato === 'Rescindido' ? 'bg-danger text-white' : 'bg-light text-primary') : ($v->estado_contrato === 'Rescindido' ? 'bg-danger' : 'bg-success') }}">{{ $v->estado_contrato }}</span>
                                    </div>
                                    @if($v->beneficiario_final)
                                        <div class="small {{ $esActual ? 'text-white-50' : 'text-muted' }} mt-1">
                                            <i class="fas fa-user-tie me-1"></i> {{ $v->beneficiario_final }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if(!$venta)
            <div class="alert alert-warning">
                No tienes ventas activas registradas en el sistema.
            </div>
        @else
        <div class="row">
            <!-- Detalles de la Venta -->
            <div class="col-lg-4">
                <div class="card card-custom">
                    <div class="card-custom-header">
                        <i class="fas fa-file-contract"></i> Resumen de tu Contrato
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="info-label">Estado</div>
                            <span class="badge {{ $venta->estado_contrato === 'Rescindido' ? 'bg-danger' : 'bg-success' }} status-badge">{{ $venta->estado_contrato }}</span>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Lotes Adquiridos</div>
                            <div class="info-value fw-bold text-dark fs-6">
                                @foreach($venta->lotes as $lote)
                                    <i class="fas fa-map-marker-alt text-primary me-1"></i> {{ $lote->nombre_completo }}<br>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="info-label">Precio Final</div>
                                <div class="info-value text-primary fw-bold fs-5">${{ number_format($venta->precio_final, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="info-label">Saldo Pendiente</div>
                                <div class="info-value text-danger fw-bold fs-5">${{ number_format(max(0, (float)$venta->precio_final - (float)$venta->total_abonado), 2) }}</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="info-label">Total Abonado</div>
                                <div class="info-value text-success fw-bold">${{ number_format($venta->total_abonado, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="info-label">Cuota Mensual</div>
                                <div class="info-value text-dark fw-bold">${{ number_format($venta->cuota_mensual, 2) }} / {{ $venta->plazo_meses }} meses</div>
                            </div>
                        </div>
                        @php
                            $abonosOrdenados = $venta->abonos->sortBy('fecha_pago')->values();
                            $numAbonos = $abonosOrdenados->count();
                            $todasCuotas = $venta->cuotas->sortBy('numero_cuota')->values();

                            // Las cuotas del final que se hayan liquidado por completo (Pagada) desaparecen de la lista
                            $cuotasVisibles = $todasCuotas->filter(function($cuota, $idx) use ($numAbonos) {
                                if ($idx >= $numAbonos && $cuota->estado === 'Pagada') {
                                    return false;
                                }
                                return true;
                            })->values();

                            $cuotasPagadasCount = min($numAbonos, $cuotasVisibles->count());
                            $cuotasPendientesCount = max(0, $cuotasVisibles->count() - $cuotasPagadasCount);
                        @endphp
                        <div class="row g-2 mb-3 pt-2 border-top">
                            <div class="col-6">
                                <div class="info-label">Cuotas Pagadas</div>
                                <div class="info-value text-success fw-bold">{{ $cuotasPagadasCount }} pagadas</div>
                            </div>
                            <div class="col-6">
                                <div class="info-label">Hacen Falta</div>
                                <div class="info-value text-danger fw-bold">{{ $cuotasPendientesCount }} cuotas</div>
                            </div>
                        </div>
                        
                        @if($venta->estado_contrato !== 'Rescindido')
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="alert('Funcionalidad de Promesa de Venta en PDF próximamente')">
                                <i class="fas fa-file-pdf"></i> Descargar Promesa de Venta
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tablas y Detalles -->
            <div class="col-lg-8">
                
                @if($venta->estado_contrato === 'Rescindido')
                <!-- Aviso de Contrato Rescindido -->
                <div class="card card-custom border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="mb-3">
                            <i class="fas fa-ban fa-4x text-danger opacity-75"></i>
                        </div>
                        <h4 class="fw-bold text-danger mb-2">Contrato Rescindido</h4>
                        <p class="text-muted fs-6 mb-4">
                            Este contrato se encuentra actualmente en estado <strong>Rescindido</strong>.
                        </p>
                        <div class="alert alert-secondary text-start p-3 px-4 rounded-3 d-inline-block border-0 shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle text-primary fs-4 me-3"></i>
                                <span class="small text-muted">
                                    El plan de cuotas y pagos no está disponible debido a la rescisión o cancelación del contrato. Para más detalles o aclaraciones, por favor contacta a la administración de <strong>Proyecto San Miguel</strong>.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                
                <!-- Plan de Cuotas y Pagos Unificado -->
                <div class="card card-custom shadow-sm border-0">
                    <div class="card-custom-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                        <div class="fw-bold text-primary fs-6">
                            <i class="fas fa-calendar-alt me-2"></i> Plan Completo de Cuotas y Pagos
                        </div>
                        <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill">
                            {{ $cuotasVisibles->count() }} Cuotas
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 540px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th>Vencimiento</th>
                                        <th>Cuota</th>
                                        <th>Monto Abonado</th>
                                        <th>Saldo Total</th>
                                        <th>Mora</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $saldoAcumulado = (float) $venta->precio_final;
                                    @endphp
                                    @forelse($cuotasVisibles as $index => $cuota)
                                    @php
                                        $abonoCorrespondiente = ($index < $numAbonos) ? $abonosOrdenados->get($index) : null;
                                        if ($abonoCorrespondiente) {
                                            $montoAbonadoMostrar = (float) $abonoCorrespondiente->monto_abonado;
                                            $saldoAcumulado = max(0, $saldoAcumulado - $montoAbonadoMostrar);
                                            $saldoMostrar = $saldoAcumulado;
                                            $moraMostrar = 0.00;
                                            $estadoTexto = 'Pagada';
                                            $fechaAbonoMostrar = $abonoCorrespondiente->fecha_pago;
                                            $fechaTransfMostrar = $abonoCorrespondiente->fecha_transferencia;
                                        } else {
                                            $montoAbonadoMostrar = 0.00;
                                            $saldoAcumulado = max(0, $saldoAcumulado - (float) $cuota->monto_total);
                                            $saldoMostrar = $saldoAcumulado;
                                            $moraMostrar = ($cuota->fecha_vencimiento < now()->format('Y-m-d')) ? (float) $cuota->mora_pendiente : 0.00;
                                            $estadoTexto = ($cuota->fecha_vencimiento < now()->format('Y-m-d') && $moraMostrar > 0) ? 'Mora' : 'Pendiente';
                                            $fechaAbonoMostrar = null;
                                            $fechaTransfMostrar = null;
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold text-muted">{{ $cuota->numero_cuota }}</td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</span>
                                            @if($fechaAbonoMostrar)
                                                <br><small class="text-success" style="font-size: 0.75rem;" title="Fecha en que se realizó el abono"><i class="fas fa-calendar-check me-1"></i>Abonado: {{ \Carbon\Carbon::parse($fechaAbonoMostrar)->format('d/m/Y') }}</small>
                                            @endif
                                            @if($fechaTransfMostrar)
                                                <br><small class="text-primary" style="font-size: 0.75rem;" title="Fecha en que se realizó la transferencia"><i class="fas fa-university me-1"></i>Transf: {{ \Carbon\Carbon::parse($fechaTransfMostrar)->format('d/m/Y') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-dark">${{ number_format($cuota->monto_total, 2) }}</span>
                                        </td>
                                        <td>
                                            @if($montoAbonadoMostrar > 0)
                                                <span class="text-success fw-bold fs-6">${{ number_format($montoAbonadoMostrar, 2) }}</span>
                                            @else
                                                <span class="text-muted">$0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-dark">${{ number_format($saldoMostrar, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($moraMostrar > 0)
                                                <span class="text-danger fw-bold">${{ number_format($moraMostrar, 2) }}</span>
                                            @else
                                                <span class="text-muted">$0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($estadoTexto === 'Pagada')
                                                <span class="badge bg-success px-2 py-1"><i class="fas fa-check me-1"></i>Pagada</span>
                                            @elseif($estadoTexto === 'Mora')
                                                <span class="badge bg-danger px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i>En Mora</span>
                                            @else
                                                <span class="badge bg-warning text-dark px-2 py-1">Pendiente</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No hay cuotas generadas para esta venta.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
        @endif

    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} Proyecto San Miguel. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
