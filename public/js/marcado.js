/**
 * Pantalla de marcado de asistencia: camara, reloj del servidor y bloqueo de
 * entrada/salida por jornada. Los datos dinamicos llegan en el formulario
 * (data-cerrado, data-avatar) y en el JSON #estado-jornadas.
 */
$(function() {
    var formulario = document.getElementById('formularioMarcado');

    if (!formulario) {
        return;
    }

    // Periodo cerrado: el informe final ya se genero, no hay nada que
    // marcar. Se deshabilitan los controles y ni el servidor acepta.
    if (formulario.dataset.cerrado === '1') {
        formulario.querySelectorAll('select, input, button').forEach(function(control) {
            control.disabled = true;
        });

        return;
    }

    var video       = document.getElementById('camara');
    var instantanea = document.getElementById('instantanea');
    var aviso       = document.getElementById('avisoCamara');
    var respaldo    = document.getElementById('respaldo');
    var campo       = document.getElementById('foto');
    var galeria     = document.getElementById('fotoGaleria');

    var btnCamara   = document.getElementById('btnCamara');
    var btnTomar    = document.getElementById('btnTomar');
    var btnRepetir  = document.getElementById('btnRepetir');
    var btnGuardar  = document.getElementById('btnGuardar');
    var textoGuardar = document.getElementById('textoGuardar');

    var selectJornada = document.getElementById('jornada');
    var entradaRadio = formulario.querySelector('input[name="tipo"][value="entrada"]');
    var salidaRadio  = formulario.querySelector('input[name="tipo"][value="salida"]');
    var estadoJornadas = JSON.parse(document.getElementById('estado-jornadas').textContent);

    var lienzo = document.createElement('canvas');
    var flujo = null;

    function mostrarAviso(mensaje, clase) {
        aviso.textContent = mensaje;
        aviso.className = 'alert mt-1 mb-2 py-2 ' + (clase || 'alert-info');
    }

    function detenerCamara() {
        if (flujo) {
            flujo.getTracks().forEach(function(pista) { pista.stop(); });
            flujo = null;
        }

        video.srcObject = null;
    }

    // Sin camara disponible se ofrece la galeria, que en el celular abre
    // la camara del telefono. En escritorio solo queda la webcam.
    function sinCamara(motivo) {
        mostrarAviso(motivo + ' Usa la galeria para tomar la foto.', 'alert-warning');
        respaldo.classList.remove('d-none');
        btnCamara.disabled = true;
        btnTomar.disabled = true;
        btnGuardar.disabled = campo.files.length === 0;
        detenerCamara();
        video.classList.add('d-none');
    }

    function activarCamara() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            sinCamara('Este navegador no permite usar la camara.');

            return;
        }

        mostrarAviso('Pide permiso para usar la camara y te mostrara la imagen.', 'alert-info');

        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function(obtenido) {
                flujo = obtenido;
                video.srcObject = obtenido;
                video.play();

                video.classList.remove('d-none');
                instantanea.classList.add('d-none');
                aviso.classList.add('d-none');

                btnCamara.disabled = true;
                btnTomar.disabled = false;
                btnRepetir.disabled = true;
                btnGuardar.disabled = campo.files.length === 0;
            })
            .catch(function() {
                sinCamara('No se pudo abrir la camara. Revisa el permiso del navegador.');
            });
    }

    function tomarFoto() {
        var ancho  = video.videoWidth  || 640;
        var alto   = video.videoHeight || 480;

        lienzo.width = ancho;
        lienzo.height = alto;
        lienzo.getContext('2d').drawImage(video, 0, 0, ancho, alto);

        lienzo.toBlob(function(blob) {
            if (!blob) {
                mostrarAviso('No se pudo tomar la foto. Intenta de nuevo.', 'alert-danger');

                return;
            }

            // El archivo se coloca en el campo del formulario para que
            // Laravel lo reciba igual que una subida normal
            var archivo = new File([blob], 'marcado-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            var datos = new DataTransfer();
            datos.items.add(archivo);
            campo.files = datos.files;

            instantanea.src = URL.createObjectURL(blob);
            instantanea.classList.remove('d-none');
            video.classList.add('d-none');
            aviso.classList.add('d-none');

            btnTomar.disabled = true;
            btnRepetir.disabled = false;
            btnGuardar.disabled = false;

            detenerCamara();
        }, 'image/jpeg', 0.9);
    }

    function repetir() {
        campo.value = '';
        instantanea.src = instantanea.dataset.avatar;
        instantanea.classList.remove('d-none');
        btnGuardar.disabled = true;
        btnTomar.disabled = true;
        btnRepetir.disabled = true;
        activarCamara();
    }

    // Bloquea entrada o salida segun lo que el pasante ya marco en la jornada
    function aplicarEstadoJornada() {
        var estado = estadoJornadas[selectJornada.value] || { entrada: false, salida: false, completo: false };

        entradaRadio.disabled = estado.entrada;
        salidaRadio.disabled = ! estado.entrada || estado.completo;

        if (! estado.entrada) {
            entradaRadio.checked = true;
            salidaRadio.checked = false;
        } else if (! estado.completo) {
            entradaRadio.checked = false;
            salidaRadio.checked = true;
        } else {
            entradaRadio.checked = false;
            salidaRadio.checked = false;
        }

        [entradaRadio, salidaRadio].forEach(function(opcion) {
            var etiqueta = opcion.closest('label');
            if (etiqueta) {
                etiqueta.classList.toggle('active', opcion.checked);
            }
        });
    }

    btnCamara.addEventListener('click', activarCamara);
    btnTomar.addEventListener('click', tomarFoto);
    btnRepetir.addEventListener('click', repetir);

    selectJornada.addEventListener('change', aplicarEstadoJornada);

    // Respaldo: la foto elegida en la galeria cuenta igual que la de la camara
    galeria.addEventListener('change', function() {
        if (!galeria.files.length) {
            return;
        }

        campo.files = galeria.files;
        instantanea.src = URL.createObjectURL(galeria.files[0]);
        instantanea.classList.remove('d-none');
        video.classList.add('d-none');
        aviso.classList.add('d-none');
        btnRepetir.disabled = false;
        btnGuardar.disabled = false;
        respaldo.classList.add('d-none');
    });

    formulario.querySelectorAll('input[name="tipo"]').forEach(function(opcion) {
        opcion.addEventListener('change', aplicarEstadoJornada);
    });

    aplicarEstadoJornada();

    // Evita el doble envio: sin esto, un segundo clic reenvia el formulario
    // con la sesion ya consumida y el pasante ve un error confuso
    formulario.addEventListener('submit', function() {
        btnGuardar.disabled = true;
        textoGuardar.textContent = 'Registrando...';
    });

    // Reloj de la hora del servidor, que es la que se va a guardar
    var reloj = document.getElementById('reloj');
    var partes = reloj.value.trim().split(':');
    var segundos = (Number(partes[0]) * 3600) + (Number(partes[1]) * 60) + Number(partes[2] || 0);

    setInterval(function() {
        segundos += 1;

        var h = Math.floor(segundos / 3600) % 24;
        var m = Math.floor(segundos / 60) % 60;
        var s = segundos % 60;

        reloj.value = [h, m, s].map(function(n) {
            return String(n).padStart(2, '0');
        }).join(':');
    }, 1000);
});
