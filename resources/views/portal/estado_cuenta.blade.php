<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta - {{ $cliente->nombres_apellidos }}</title>
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
                            <span class="badge bg-success status-badge">{{ $venta->estado_contrato }}</span>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Lotes Adquiridos</div>
                            <div class="info-value">
                                @foreach($venta->lotes as $lote)
                                    Bloque {{ $lote->bloque->nombre ?? 'N/A' }}, Lote {{ $lote->numero_lote ?? 'N/A' }}<br>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Precio Final</div>
                            <div class="info-value text-primary">${{ number_format($venta->precio_final, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Total Abonado</div>
                            <div class="info-value text-success">${{ number_format($venta->total_abonado, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Cuota Mensual</div>
                            <div class="info-value">${{ number_format($venta->cuota_mensual, 2) }} / {{ $venta->plazo_meses }} meses</div>
                        </div>
                        
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="alert('Funcionalidad de Promesa de Venta en PDF próximamente')">
                                <i class="fas fa-file-pdf"></i> Descargar Promesa de Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablas y Detalles -->
            <div class="col-lg-8">
                
                <!-- Plan de Pagos -->
                <div class="card card-custom">
                    <div class="card-custom-header">
                        <i class="fas fa-calendar-alt"></i> Próximas Cuotas y Mora
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimiento</th>
                                        <th>Monto</th>
                                        <th>Saldo</th>
                                        <th>Mora</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($venta->cuotas->take(12) as $cuota)
                                    <tr>
                                        <td>{{ $cuota->numero_cuota }}</td>
                                        <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                                        <td>${{ number_format($cuota->monto_total, 2) }}</td>
                                        <td>
                                            @if($cuota->saldo_restante > 0)
                                                <span class="fw-bold">${{ number_format($cuota->saldo_restante, 2) }}</span>
                                            @else
                                                <span class="text-muted">$0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cuota->mora_pendiente > 0)
                                                <span class="text-danger fw-bold">${{ number_format($cuota->mora_pendiente, 2) }}</span>
                                            @else
                                                <span class="text-muted">$0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cuota->estado == 'Pagada')
                                                <span class="badge bg-success">Pagada</span>
                                            @elseif($cuota->estado == 'Mora')
                                                <span class="badge bg-danger">En Mora</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No hay cuotas generadas para esta venta.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($venta->cuotas->count() > 12)
                        <div class="text-center p-3 border-top bg-light">
                            <small class="text-muted">Se muestran las primeras 12 cuotas. Contacte administración para el plan completo.</small>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Historial de Pagos -->
                <div class="card card-custom">
                    <div class="card-custom-header">
                        <i class="fas fa-receipt"></i> Historial de Pagos (Recibos)
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Concepto</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($venta->abonos as $abono)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($abono->fecha_pago)->format('d/m/Y') }}</td>
                                        <td class="text-success fw-bold">+${{ number_format($abono->monto_abonado, 2) }}</td>
                                        <td>{{ $abono->tipo_pago }}</td>
                                        <td>{{ $abono->metodo_pago ?? 'Efectivo' }}</td>
                                        <td>
                                            {{ $abono->referencia ?? '-' }}
                                            @if($abono->cuenta_destino)
                                                <br><small class="text-muted"><i class="fas fa-university"></i> {{ $abono->cuenta_destino }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('abonos.imprimir', $abono->id_abono) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-download"></i> Recibo
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No se han registrado pagos aún.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

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
