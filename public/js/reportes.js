$(function () {

    // La tabla de modulos es simple: solo vista, sin los botones de
    // descarga que DataTables trae por defecto.
    $('#tablaReportes').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "ordering": false,
        "searching": false,
        "paging": false,
        "info": false,
    });

    var modalPdf = $('#modalPdfAsistencias');
    var modalExcel = $('#modalExcelAsistencias');

    // JSON de pasantes inyectado por la vista (id, nombre, cedula,
    // fecha_ingreso, completo, informe, reporte_id, urls de descarga/anular)
    var pasantes = [];
    try {
        pasantes = JSON.parse(modalPdf.attr('data-pasantes') || '[]');
    } catch (e) {
        console.error('No se pudieron cargar los pasantes desde la vista:', e);
    }

    function todayISO() {
        var d = new Date();
        var mes = ('0' + (d.getMonth() + 1)).slice(-2);
        var dia = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + mes + '-' + dia;
    }

    function llenarPasantes($select) {
        $select.empty();
        $select.append($('<option>', { value: '', text: 'Seleccione un pasante' }));
        pasantes.forEach(function (p) {
            $select.append($('<option>', { value: p.id, text: p.nombre + ' — C.I. ' + p.cedula }));
        });
    }

    function pasantePorId(id) {
        id = parseInt(id, 10);
        for (var i = 0; i < pasantes.length; i++) {
            if (pasantes[i].id === id) {
                return pasantes[i];
            }
        }
        return null;
    }

    // El "desde" no puede ser anterior a la fecha de ingreso ni posterior a
    // hoy; el "hasta" nunca puede rebasar hoy ni quedar antes del "desde".
    function aplicarRangoFechas($select, $desde, $hasta) {
        var p = pasantePorId($select.val());

        $desde.attr('min', p ? p.fecha_ingreso : '');
        $desde.attr('max', todayISO());
        $hasta.attr('min', $desde.val() || (p ? p.fecha_ingreso : ''));
        $hasta.attr('max', todayISO());

        if (p && p.fecha_ingreso && $desde.val() && $desde.val() < p.fecha_ingreso) {
            $desde.val(p.fecha_ingreso);
        }
        if ($hasta.val() > todayISO()) {
            $hasta.val(todayISO());
        }
    }

    function validarBitacoraForm($select, $desde, $hasta) {
        if (! $select.val()) {
            mostrarToast('error', 'Falta el pasante', 'Debe escoger un pasante.');
            return false;
        }
        if (! $desde.val() || ! $hasta.val()) {
            mostrarToast('error', 'Rango incompleto', 'Debe indicar las fechas "desde" y "hasta".');
            return false;
        }
        if ($desde.val() > $hasta.val()) {
            mostrarToast('error', 'Rango inválido', 'La fecha inicial no puede ser posterior a la final.');
            return false;
        }
        return true;
    }

    // ------------------------------------------------------------------
    // Modal PDF de asistencias
    // ------------------------------------------------------------------

    llenarPasantes($('#selectPasantePdf'));
    llenarPasantes($('#selectPasanteExcel'));

    function modoEsInforme() {
        return $('#tipoReportePdf').val() === 'informe';
    }

    function pasanteSeleccionadoPdf() {
        return pasantePorId($('#selectPasantePdf').val());
    }

    function reiniciarModalPdf() {
        $('#tipoReportePdf').val('bitacora');
        $('#selectPasantePdf').val('');
        $('#hiddenPasantePdf').val('');
        $('#hiddenPasanteInforme').val('');
        $('#desdePdf').val('');
        $('#hastaPdf').val('');
        actualizarModoPdf();
    }

    function actualizarModoPdf() {
        var esInforme = modoEsInforme();
        $('#contenidoBitacoraPdf').toggle(!esInforme);
        $('#contenidoInformePdf').toggle(esInforme);
        if (esInforme) {
            actualizarEstadoInforme();
        }
    }

    function actualizarEstadoInforme() {
        var p = pasanteSeleccionadoPdf();
        var $info = $('#infoInformePdf');
        var $pendiente = $('#grupoInformePendiente');
        var $generado = $('#grupoInformeGenerado');

        if (!p) {
            $pendiente.hide();
            $generado.hide();
            $info.removeClass('alert-success').addClass('alert-info')
                .text('Seleccione un pasante para ver el estado de su informe final.');
            return;
        }

        $('#hiddenPasanteInforme').val(p.id);

        if (p.informe) {
            $pendiente.hide();
            $generado.show();
            $('#btnDescargarInforme').attr('href', p.url_descargar_informe);
            $('#formAnularInforme').attr('action', p.url_anular);
            $info.removeClass('alert-info').addClass('alert-success')
                .text(p.nombre + ' — informe final generado. El periodo de registro está cerrado.');
        } else {
            $generado.hide();
            $pendiente.show();
            $('#btnVistaPrevia').attr('href', modalPdf.attr('data-url-preview') + '?pasante_id=' + p.id);
            $info.removeClass('alert-success').addClass('alert-info')
                .text(p.nombre + ' — ' + (p.completo
                    ? 'ya cumple las horas de práctica de su carrera. Puede generar el informe.'
                    : 'aún no cumple las horas de práctica de su carrera.'));
        }
    }

    $('#tipoReportePdf').on('change', actualizarModoPdf);

    $('#selectPasantePdf').on('change', function () {
        var p = pasanteSeleccionadoPdf();
        $('#hiddenPasantePdf').val(p ? p.id : '');
        aplicarRangoFechas($(this), $('#desdePdf'), $('#hastaPdf'));
        if (modoEsInforme()) {
            actualizarEstadoInforme();
        }
    });

    $('#desdePdf').on('change', function () {
        $('#hastaPdf').attr('min', this.value || '');
    });

    modalPdf.on('show.bs.modal', reiniciarModalPdf);

    // Descargar bitacora PDF
    $('#formBitacoraPdf').on('submit', function (e) {
        if (!validarBitacoraForm($('#selectPasantePdf'), $('#desdePdf'), $('#hastaPdf'))) {
            e.preventDefault();
        }
    });

    // Generar informe final: la validacion de horas vive aqui y en el
    // controlador. Sin horas cumplidas la alerta corta el envio.
    $('#formGenerarInforme').on('submit', function (e) {
        var p = pasanteSeleccionadoPdf();

        if (!p) {
            e.preventDefault();
            mostrarToast('error', 'Falta el pasante', 'Debe escoger un pasante.');
            return;
        }

        if (!p.completo) {
            e.preventDefault();
            mostrarToast('error', 'No se puede generar el informe',
                'El pasante aún no cumple las horas de práctica requeridas por su carrera.');
            return;
        }

        e.preventDefault();
        var form = this;
        Swal.fire({
            title: '¿Generar el informe final?',
            text: 'Se guardará el PDF con las horas actuales y el periodo de registro del pasante quedará cerrado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                form.submit();
            }
        });
    });

    // Reabrir el informe: confirma y reabre el periodo de registro
    $('#formAnularInforme').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: '¿Reabrir el informe?',
            text: 'Se borrará el PDF, el pasante volverá a poder registrar asistencias y dejará de poder descargar su informe final.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, reabrir',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                form.submit();
            }
        });
    });

    // ------------------------------------------------------------------
    // Modal Excel de la bitacora
    // ------------------------------------------------------------------

    $('#selectPasanteExcel').on('change', function () {
        aplicarRangoFechas($(this), $('#desdeExcel'), $('#hastaExcel'));
    });

    $('#desdeExcel').on('change', function () {
        $('#hastaExcel').attr('min', this.value || '');
    });

    modalExcel.on('show.bs.modal', function () {
        $('#selectPasanteExcel').val('');
        $('#desdeExcel').val('');
        $('#hastaExcel').val('');
    });

    $('#formExcelAsistencias').on('submit', function (e) {
        if (!validarBitacoraForm($('#selectPasanteExcel'), $('#desdeExcel'), $('#hastaExcel'))) {
            e.preventDefault();
        }
    });

});