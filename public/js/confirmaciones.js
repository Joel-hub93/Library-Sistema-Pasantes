/**
 * Confirmacion antes de eliminar un registro (formularios .form-eliminar).
 * El titulo y el texto vienen del form en data-confirm-title y data-confirm-text.
 */
$(function() {
    $('.form-eliminar').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: form.dataset.confirmTitle || '¿Eliminar este registro?',
            text: form.dataset.confirmText || 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(resultado) {
            if (resultado.isConfirmed) {
                form.submit();
            }
        });
    });
});
