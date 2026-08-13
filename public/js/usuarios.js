document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('modalEliminar');
    if (!modalElement) return;

    const modalEliminar = new bootstrap.Modal(modalElement);
    const formEliminar = document.getElementById('formEliminar');
    const nombreUsuarioModal = document.getElementById('nombreUsuarioModal');

    // Escuchar clics en los botones de eliminar
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-nombre');

            // Asignar el nombre del usuario al texto del modal
            nombreUsuarioModal.textContent = userName;

            // Configurar la URL de la acción del formulario dinámicamente
            formEliminar.action = `/usuarios/${userId}`;

            // Mostrar el modal
            modalEliminar.show();
        });
    });
});