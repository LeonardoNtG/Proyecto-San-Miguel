/**
 * Paginador Interactivo para Tablas de Reportes
 * Permite paginar cualquier tabla HTML con búsqueda en vivo, selector de tamaño de página
 * y controles de navegación responsivos.
 */
function inicializarPaginacionReporte(config) {
    const tabla = document.querySelector(config.tablaSelector);
    if (!tabla) return;

    const contenedorPaginacion = document.querySelector(config.paginacionSelector);
    const inputBuscador = config.buscadorSelector ? document.querySelector(config.buscadorSelector) : null;
    const selectPorPagina = config.porPaginaSelector ? document.querySelector(config.porPaginaSelector) : null;

    let filasOriginales = Array.from(tabla.querySelectorAll('tbody tr[data-rf-fila], tbody tr'));
    // Filtrar posibles filas vacías o de "sin resultados"
    filasOriginales = filasOriginales.filter(f => !f.classList.contains('fila-vacia') && f.querySelectorAll('td').length > 1);

    let filasFiltradas = [...filasOriginales];
    let paginaActual = 1;
    let porPagina = config.porPaginaDefault || 25;

    function aplicarFiltroYPaginar() {
        const termino = inputBuscador ? inputBuscador.value.toLowerCase().trim() : '';

        if (termino === '') {
            filasFiltradas = [...filasOriginales];
        } else {
            filasFiltradas = filasOriginales.filter(fila => {
                return fila.textContent.toLowerCase().includes(termino);
            });
        }

        const totalRegistros = filasFiltradas.length;
        const totalPaginas = porPagina === 'todos' ? 1 : Math.ceil(totalRegistros / porPagina) || 1;

        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }
        if (paginaActual < 1) {
            paginaActual = 1;
        }

        // Ocultar todas las filas originales primero
        filasOriginales.forEach(f => f.style.display = 'none');

        // Mostrar solo las filas de la página actual
        const inicio = porPagina === 'todos' ? 0 : (paginaActual - 1) * porPagina;
        const fin = porPagina === 'todos' ? totalRegistros : Math.min(inicio + porPagina, totalRegistros);

        for (let i = inicio; i < fin; i++) {
            if (filasFiltradas[i]) {
                filasFiltradas[i].style.display = '';
            }
        }

        renderizarControles(totalRegistros, totalPaginas, inicio, fin);
    }

    function renderizarControles(totalRegistros, totalPaginas, inicio, fin) {
        if (!contenedorPaginacion) return;

        if (totalRegistros === 0) {
            contenedorPaginacion.innerHTML = `
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100 p-2 text-muted small">
                    <span>No se encontraron registros.</span>
                </div>
            `;
            return;
        }

        const registroDesde = totalRegistros > 0 ? inicio + 1 : 0;
        const registroHasta = fin;

        let botonesHtml = '';
        if (totalPaginas > 1) {
            botonesHtml += `
                <button type="button" class="btn btn-sm btn-outline-secondary ${paginaActual === 1 ? 'disabled' : ''}" data-pag="1" title="Primera Página">«</button>
                <button type="button" class="btn btn-sm btn-outline-secondary ${paginaActual === 1 ? 'disabled' : ''}" data-pag="${paginaActual - 1}" title="Anterior">‹</button>
            `;

            // Páginas visibles
            const maxBotones = 5;
            let startPag = Math.max(1, paginaActual - Math.floor(maxBotones / 2));
            let endPag = Math.min(totalPaginas, startPag + maxBotones - 1);
            if (endPag - startPag + 1 < maxBotones) {
                startPag = Math.max(1, endPag - maxBotones + 1);
            }

            for (let p = startPag; p <= endPag; p++) {
                botonesHtml += `
                    <button type="button" class="btn btn-sm ${p === paginaActual ? 'btn-primary active fw-bold' : 'btn-outline-secondary'}" data-pag="${p}">${p}</button>
                `;
            }

            botonesHtml += `
                <button type="button" class="btn btn-sm btn-outline-secondary ${paginaActual === totalPaginas ? 'disabled' : ''}" data-pag="${paginaActual + 1}" title="Siguiente">›</button>
                <button type="button" class="btn btn-sm btn-outline-secondary ${paginaActual === totalPaginas ? 'disabled' : ''}" data-pag="${totalPaginas}" title="Última Página">»</button>
            `;
        }

        contenedorPaginacion.innerHTML = `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100 p-2">
                <div class="text-muted small">
                    Mostrando <strong class="text-dark">${registroDesde}</strong> a <strong class="text-dark">${registroHasta}</strong> de <strong class="text-dark">${totalRegistros}</strong> registros
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    ${botonesHtml}
                </div>
            </div>
        `;

        // Eventos a botones
        contenedorPaginacion.querySelectorAll('button[data-pag]').forEach(btn => {
            btn.addEventListener('click', function () {
                const targetPag = parseInt(this.getAttribute('data-pag'));
                if (!isNaN(targetPag) && targetPag !== paginaActual && targetPag >= 1 && targetPag <= totalPaginas) {
                    paginaActual = targetPag;
                    aplicarFiltroYPaginar();
                }
            });
        });
    }

    if (inputBuscador) {
        inputBuscador.addEventListener('input', function () {
            paginaActual = 1;
            aplicarFiltroYPaginar();
        });
    }

    if (selectPorPagina) {
        selectPorPagina.addEventListener('change', function () {
            porPagina = this.value === 'todos' ? 'todos' : parseInt(this.value);
            paginaActual = 1;
            aplicarFiltroYPaginar();
        });
    }

    // Inicializar
    aplicarFiltroYPaginar();
}
