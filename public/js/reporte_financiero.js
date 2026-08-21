document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('rf-form-filtros');
    if (!form) {
        return;
    }

    var selectPeriodo = document.getElementById('rf-periodo');
    var grupoMes = document.getElementById('rf-grupo-mes');
    var grupoAnio = document.getElementById('rf-grupo-anio');
    var grupoFecha = document.getElementById('rf-grupo-fecha');
    var selectAnio = document.getElementById('rf-anio');
    var selectMes = document.getElementById('rf-mes');
    var inputFecha = document.getElementById('rf-fecha');
    var enlacesExportar = document.querySelectorAll('[data-rf-exportar]');

    function actualizarVisibilidadGrupos() {
        if (!selectPeriodo) {
            return;
        }
        var valor = selectPeriodo.value;
        if (grupoFecha) grupoFecha.classList.toggle('d-none', valor !== 'dia');
        if (grupoAnio) grupoAnio.classList.toggle('d-none', !['mes', 'anio', 'ytd'].includes(valor));
        if (grupoMes) grupoMes.classList.toggle('d-none', valor !== 'mes');
    }

    function actualizarEnlacesExportar() {
        if (!enlacesExportar.length) {
            return;
        }
        var params = new URLSearchParams();
        if (selectPeriodo) params.set('periodo', selectPeriodo.value);
        if (selectPeriodo && selectPeriodo.value === 'dia' && inputFecha) {
            params.set('fecha', inputFecha.value);
        }
        if (selectPeriodo && ['mes', 'anio', 'ytd'].includes(selectPeriodo.value) && selectAnio) {
            params.set('anio', selectAnio.value);
        }
        if (selectMes && selectPeriodo && selectPeriodo.value === 'mes') {
            params.set('mes', selectMes.value);
        }

        enlacesExportar.forEach(function (enlace) {
            var base = enlace.getAttribute('data-rf-base');
            enlace.setAttribute('href', base + '?' + params.toString());
        });
    }

    function refrescar() {
        actualizarVisibilidadGrupos();
        actualizarEnlacesExportar();
    }

    [selectPeriodo, selectAnio, selectMes, inputFecha].forEach(function (campo) {
        if (!campo) return;
        campo.addEventListener('change', function () {
            refrescar();
            form.submit();
        });
    });

    refrescar();

    // Animación ligera de conteo para los KPIs principales
    document.querySelectorAll('[data-rf-contador]').forEach(function (el) {
        var valorFinal = parseFloat(el.getAttribute('data-rf-contador'));
        if (isNaN(valorFinal)) return;

        var esMoneda = el.hasAttribute('data-rf-moneda');
        var duracion = 600;
        var inicio = null;

        function paso(timestamp) {
            if (!inicio) inicio = timestamp;
            var progreso = Math.min((timestamp - inicio) / duracion, 1);
            var valorActual = valorFinal * progreso;
            el.textContent = esMoneda
                ? '$' + valorActual.toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : Math.round(valorActual).toLocaleString('es-NI');
            if (progreso < 1) {
                window.requestAnimationFrame(paso);
            }
        }
        window.requestAnimationFrame(paso);
    });

    // Filtro rápido (cliente-lado) sobre la tabla de abonos, por nombre o lote
    var buscador = document.getElementById('rf-buscador');
    var tablaAbonos = document.getElementById('rf-tabla-abonos');
    if (buscador && tablaAbonos) {
        buscador.addEventListener('input', function () {
            var termino = buscador.value.trim().toLowerCase();
            var filas = tablaAbonos.querySelectorAll('tbody tr[data-rf-fila]');
            filas.forEach(function (fila) {
                var texto = fila.textContent.toLowerCase();
                fila.style.display = texto.indexOf(termino) === -1 ? 'none' : '';
            });
        });
    }
});
