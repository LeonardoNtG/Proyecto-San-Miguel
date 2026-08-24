document.addEventListener('DOMContentLoaded', function () {
    var COLOR_PRIMARY = '#4e73df';
    var COLOR_SUCCESS = '#1cc88a';
    var COLOR_DANGER = '#e74a3b';
    var COLOR_INFO = '#36b9cc';
    var COLOR_WARNING = '#f6c23e';

    function rgba(hex, alpha) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    // ------------------------------------------------------------
    // Filtros: alternar Mes/Año según agrupación, auto-submit
    // ------------------------------------------------------------
    var form = document.getElementById('gr-form-filtros');
    if (form) {
        var selectAgrupacion = document.getElementById('gr-agrupacion');
        var grupoAnio = document.getElementById('gr-grupo-anio');
        var grupoMes = document.getElementById('gr-grupo-mes');
        var selectAnio = document.getElementById('gr-anio');
        var selectMes = document.getElementById('gr-mes');

        function actualizarVisibilidad() {
            if (!selectAgrupacion) return;
            var valor = selectAgrupacion.value;
            if (grupoAnio) grupoAnio.classList.toggle('d-none', valor === 'anio');
            if (grupoMes) grupoMes.classList.toggle('d-none', valor !== 'dia');
        }

        [selectAgrupacion, selectAnio, selectMes].forEach(function (campo) {
            if (!campo) return;
            campo.addEventListener('change', function () {
                actualizarVisibilidad();
                form.submit();
            });
        });

        actualizarVisibilidad();
    }

    // ------------------------------------------------------------
    // Botón Imprimir PDF (usa el diálogo de impresión del navegador)
    // ------------------------------------------------------------
    var btnImprimir = document.getElementById('gr-btn-imprimir');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', function () {
            window.print();
        });
    }

    // ------------------------------------------------------------
    // Contador animado para las tarjetas KPI
    // ------------------------------------------------------------
    document.querySelectorAll('[data-gr-contador]').forEach(function (el) {
        var valorFinal = parseFloat(el.getAttribute('data-gr-contador'));
        if (isNaN(valorFinal)) return;

        var esMoneda = el.hasAttribute('data-gr-moneda');
        var duracion = 650;
        var inicio = null;

        function paso(timestamp) {
            if (!inicio) inicio = timestamp;
            var progreso = Math.min((timestamp - inicio) / duracion, 1);
            var valorActual = valorFinal * progreso;
            el.textContent = esMoneda
                ? '$' + valorActual.toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : Math.round(valorActual).toLocaleString('es-NI');
            if (progreso < 1) window.requestAnimationFrame(paso);
        }
        window.requestAnimationFrame(paso);
    });

    // ------------------------------------------------------------
    // Gráficos (Chart.js) — los datos vienen inyectados en window.graficosData
    // ------------------------------------------------------------
    var datos = window.graficosData;
    if (!datos || typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Nunito', -apple-system, sans-serif";
    Chart.defaults.color = '#858796';

    // 1) Ingresos vs Gastos (barras agrupadas + línea de balance)
    var ctxComparativo = document.getElementById('grChartComparativo');
    if (ctxComparativo) {
        new Chart(ctxComparativo, {
            data: {
                labels: datos.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Ingresos',
                        data: datos.dataIngresos,
                        backgroundColor: rgba(COLOR_SUCCESS, 0.75),
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        type: 'bar',
                        label: 'Gastos',
                        data: datos.dataGastos,
                        backgroundColor: rgba(COLOR_DANGER, 0.75),
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'Balance Neto',
                        data: datos.dataBalance,
                        borderColor: COLOR_PRIMARY,
                        backgroundColor: rgba(COLOR_PRIMARY, 0.15),
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': $' + ctx.parsed.y.toLocaleString('es-NI', { minimumFractionDigits: 2 });
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function (v) { return '$' + v.toLocaleString('es-NI'); } },
                    },
                },
            },
        });
    }

    // 2) Ingresos (línea de área)
    var ctxIngresos = document.getElementById('grChartIngresos');
    if (ctxIngresos) {
        new Chart(ctxIngresos, {
            type: 'line',
            data: {
                labels: datos.labels,
                datasets: [{
                    label: 'Ingresos',
                    data: datos.dataIngresos,
                    borderColor: COLOR_SUCCESS,
                    backgroundColor: rgba(COLOR_SUCCESS, 0.15),
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) { return 'Ingresos: $' + ctx.parsed.y.toLocaleString('es-NI', { minimumFractionDigits: 2 }); },
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function (v) { return '$' + v.toLocaleString('es-NI'); } } },
                },
            },
        });
    }

    // 3) Gastos (línea de área)
    var ctxGastos = document.getElementById('grChartGastos');
    if (ctxGastos) {
        new Chart(ctxGastos, {
            type: 'line',
            data: {
                labels: datos.labels,
                datasets: [{
                    label: 'Gastos',
                    data: datos.dataGastos,
                    borderColor: COLOR_DANGER,
                    backgroundColor: rgba(COLOR_DANGER, 0.15),
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) { return 'Gastos: $' + ctx.parsed.y.toLocaleString('es-NI', { minimumFractionDigits: 2 }); },
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function (v) { return '$' + v.toLocaleString('es-NI'); } } },
                },
            },
        });
    }

    // 4) Contratos por Estado, por bucket (barras apiladas)
    var ctxContratos = document.getElementById('grChartContratos');
    if (ctxContratos) {
        new Chart(ctxContratos, {
            type: 'bar',
            data: {
                labels: datos.labels,
                datasets: [
                    { label: 'Vigentes', data: datos.dataVigente, backgroundColor: COLOR_PRIMARY, stack: 'contratos', borderRadius: 3 },
                    { label: 'Finalizados', data: datos.dataFinalizado, backgroundColor: COLOR_SUCCESS, stack: 'contratos', borderRadius: 3 },
                    { label: 'Rescindidos', data: datos.dataRescindido, backgroundColor: COLOR_DANGER, stack: 'contratos', borderRadius: 3 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    // 5) Distribución actual de contratos (doughnut)
    var ctxDistribucion = document.getElementById('grChartDistribucion');
    if (ctxDistribucion) {
        new Chart(ctxDistribucion, {
            type: 'doughnut',
            data: {
                labels: ['Vigentes', 'Finalizados', 'Rescindidos'],
                datasets: [{
                    data: [datos.totalVigentes, datos.totalFinalizados, datos.totalRescindidos],
                    backgroundColor: [COLOR_PRIMARY, COLOR_SUCCESS, COLOR_DANGER],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }
});
