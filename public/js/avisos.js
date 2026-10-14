/**
 * Avisos breves de SweetAlert2 en la esquina superior derecha.
 * Se usan desde las vistas con mostrarToast(icono, titulo, mensaje).
 */
window.mostrarToast = function(icono, titulo, mensaje) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icono,
        title: titulo,
        text: mensaje,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
};
