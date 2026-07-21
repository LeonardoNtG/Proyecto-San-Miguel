@extends('template') {{-- Hereda la plantilla principal --}}

@section('titulo', 'Inicio') {{-- Define el contenido de la sección 'titulo' --}}


@section('contenido')
<link rel="stylesheet" href="reporte.css">
<div class="container py-4">
    <h2 class="mb-4 fw-bold text-dark">Avance del Sistema: Análisis Mensual</h2>

    <div class="row mb-4">
    <div class="col-md-3">
        <div class="card-widget shadow-sm">
            <div class="widget-icon bg-success-light text-success">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="widget-content">
                <span class="widget-label">Ingresos (2024)</span>
                <h4 class="widget-value">${{ number_format($totalIngresosAnio, 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-widget shadow-sm">
            <div class="widget-icon bg-danger-light text-danger">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="widget-content">
                <span class="widget-label">Gastos (2024)</span>
                <h4 class="widget-value">${{ number_format($totalEgresosAnio, 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-widget shadow-sm">
            <div class="widget-icon bg-primary-light text-primary">
                <i class="fas fa-vault"></i>
            </div>
            <div class="widget-content">
                <span class="widget-label">Balance Neto</span>
                <h4 class="widget-value">${{ number_format($balanceNetoAnio, 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-widget shadow-sm">
            <div class="widget-icon bg-info-light text-info">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-content">
                <span class="widget-label">Total Clientes</span>
                <h4 class="widget-value">{{ $totalClientesTotal }}</h4>
            </div>
        </div>
    </div>
</div>
    <div class="row">
        {{-- Gráfica de Dinero (Ingresos vs Egresos) --}}
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Flujo de Caja (Ingresos vs Egresos)</div>
                <div class="card-body">
                    <canvas id="chartFlujoCaja" height="130"></canvas>
                </div>
            </div>
        </div>

        {{-- Gráfica de Clientes --}}
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Nuevos Clientes</div>
                <div class="card-body">
                    <canvas id="chartClientes" height="315"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
{{-- CDN de Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // 1. Datos del Flujo de Caja (Barras)
    const ctxFlujo = document.getElementById('chartFlujoCaja');
    new Chart(ctxFlujo, {
        type: 'bar',
        data: {
            labels: @json($meses),
            datasets: [{
                label: 'Ingresos ($)',
                data: @json($dataIngresos),
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: '#2ecc71',
                borderWidth: 1
            }, {
                label: 'Egresos ($)',
                data: @json($dataEgresos),
                backgroundColor: 'rgba(231, 76, 60, 0.7)',
                borderColor: '#e74c3c',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 2. Datos de Clientes (Línea)
    const ctxClientes = document.getElementById('chartClientes');
    new Chart(ctxClientes, {
        type: 'line',
        data: {
            labels: @json($meses),
            datasets: [{
                label: 'Clientes Registrados',
                data: @json($dataClientes),
                fill: true,
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: '#3498db',
                tension: 0.4,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
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